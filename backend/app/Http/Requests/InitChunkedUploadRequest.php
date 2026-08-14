<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitChunkedUploadRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt',
        'mp4', 'mov', 'avi',
    ];

    private const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'video/mp4', 'video/quicktime', 'video/x-msvideo',
    ];

    private const MAX_FILE_SIZE_BYTES = 51200 * 1024; // 50MB

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\s_\-\.]+$/i',
                'regex:/\.(jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx|txt|mp4|mov|avi)$/i',
            ],
            'total_size' => [
                'required',
                'integer',
                'min:1',
                'max:'.self::MAX_FILE_SIZE_BYTES,
            ],
            'mime_type' => [
                'nullable',
                'string',
                'max:100',
                'in:'.implode(',', self::ALLOWED_MIMES),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file_name.regex' => 'Unsupported file name. Allowed extensions: '.implode(', ', self::ALLOWED_EXTENSIONS).'.',
            'total_size.max' => 'File size exceeds maximum limit of 50MB.',
            'mime_type.in' => 'Unsupported file type. Allowed types: images (jpg, png, gif, webp), documents (pdf, doc, docx, xls, xlsx, txt), and videos (mp4, mov, avi).',
        ];
    }
}
