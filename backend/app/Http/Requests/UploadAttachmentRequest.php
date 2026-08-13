<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:51200',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain,video/mp4,video/quicktime,video/x-msvideo',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimetypes' => 'Unsupported file type. Allowed types: images (jpg, png, gif, webp), documents (pdf, doc, docx, xls, xlsx, txt), and videos (mp4, mov, avi).',
            'file.max' => 'File size exceeds maximum limit of 50MB.',
        ];
    }
}
