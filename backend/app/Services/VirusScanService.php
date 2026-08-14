<?php

namespace App\Services;

use App\Models\TaskAttachment;
use App\Models\VirusScanResult;
use Illuminate\Support\Facades\Storage;

class VirusScanService
{
    private const DISK = 'attachments';

    private const QUARANTINE_DIR = 'quarantine';

    private const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'vbs', 'js', 'wsf', 'ps1',
        'msi', 'dll', 'sys', 'drv', 'ocx', 'cpl', 'jar', 'vb', 'vbe',
    ];

    private const SUSPICIOUS_EXTENSIONS = [
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2',
        'iso', 'img', 'dmg',
        'sh', 'bash', 'zsh', 'fish',
        'py', 'rb', 'pl', 'php',
    ];

    private const MALICIOUS_PATTERNS = [
        'virus', 'malware', 'trojan', 'backdoor', 'exploit', 'hack', 'crack',
        'keygen', 'patch', 'loader', 'dropper', 'spyware', 'adware', 'ransomware',
        'worm', 'rootkit', 'backdoor', 'payload', 'shellcode',
    ];

    private const DANGEROUS_MIME_TYPES = [
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-sh',
        'application/x-python',
        'text/vbscript',
        'application/x-msi',
        'application/octet-stream',
    ];

    public function scan(TaskAttachment $attachment, ?int $userId = null): VirusScanResult
    {
        $existing = VirusScanResult::where('task_attachment_id', $attachment->id)->latest()->first();

        if ($existing && $existing->status === 'pending') {
            return $existing;
        }

        $threats = [];
        $status = 'clean';
        $actionTaken = 'none';

        $extension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
        $fileName = strtolower($attachment->file_name);
        $mimeType = strtolower($attachment->mime_type ?? '');

        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $threats[] = "Dangerous file extension detected: .{$extension}";
            $status = 'infected';
        }

        if (in_array($extension, self::SUSPICIOUS_EXTENSIONS)) {
            $threats[] = "Suspicious archive/script extension detected: .{$extension}";
            if ($status === 'clean') {
                $status = 'suspicious';
            }
        }

        foreach (self::MALICIOUS_PATTERNS as $pattern) {
            if (str_contains($fileName, $pattern)) {
                $threats[] = "Malicious pattern detected in filename: {$pattern}";
                $status = 'infected';
                break;
            }
        }

        if (in_array($mimeType, self::DANGEROUS_MIME_TYPES)) {
            $threats[] = "Dangerous MIME type detected: {$mimeType}";
            $status = 'infected';
        }

        if ($status === 'infected') {
            $actionTaken = 'quarantined';
            $this->quarantineFile($attachment);
        }

        $result = VirusScanResult::create([
            'task_attachment_id' => $attachment->id,
            'user_id' => $userId,
            'scan_engine' => 'simulated-clamav',
            'status' => $status,
            'threats_found' => empty($threats) ? null : json_encode($threats),
            'action_taken' => $actionTaken,
            'scanned_at' => now(),
        ]);

        return $result;
    }

    public function quarantineFile(TaskAttachment $attachment): void
    {
        $disk = Storage::disk(self::DISK);
        $fileName = basename($attachment->file_path);
        $quarantinePath = self::QUARANTINE_DIR.'/'.$fileName;

        if (str_starts_with($attachment->file_path, self::QUARANTINE_DIR.'/')) {
            return;
        }

        if ($disk->exists($attachment->file_path)) {
            $disk->makeDirectory(self::QUARANTINE_DIR);
            $disk->move($attachment->file_path, $quarantinePath);
            $attachment->update([
                'file_path' => $quarantinePath,
            ]);
        }

        if ($attachment->thumbnail_path && $disk->exists($attachment->thumbnail_path)) {
            $disk->delete($attachment->thumbnail_path);
            $attachment->update([
                'thumbnail_path' => null,
                'thumbnail_size' => null,
            ]);
        }
    }

    public function getScanResult(TaskAttachment $attachment): ?VirusScanResult
    {
        return VirusScanResult::where('task_attachment_id', $attachment->id)->latest()->first();
    }

    public function isClean(TaskAttachment $attachment): bool
    {
        $result = $this->getScanResult($attachment);

        return $result && $result->status === 'clean';
    }

    public function isInfected(TaskAttachment $attachment): bool
    {
        $result = $this->getScanResult($attachment);

        return $result && $result->status === 'infected';
    }
}
