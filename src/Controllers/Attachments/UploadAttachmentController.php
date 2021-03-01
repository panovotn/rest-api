<?php declare(strict_types=1);

namespace Reconmap\Controllers\Attachments;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Reconmap\Controllers\Controller;
use Reconmap\Models\Attachment;
use Reconmap\Repositories\AttachmentRepository;
use Reconmap\Services\AttachmentFilePath;

class UploadAttachmentController extends Controller
{
    public function __construct(private AttachmentRepository $attachmentRepository,
                                private AttachmentFilePath $attachmentFilePathService)
    {
    }

    public function __invoke(ServerRequestInterface $request, array $args): array
    {
        $params = $request->getParsedBody();
        $parentType = $params['parentType'];
        $parentId = (int)$params['parentId'];

        $userId = $request->getAttribute('userId');
        $files = $request->getUploadedFiles()['attachment'];

        foreach ($files as $file) {
            /** @var UploadedFileInterface $file */
            $this->logger->debug('file uploaded', ['filename' => $file->getClientFilename(), 'type' => $file->getClientMediaType(), 'size' => $file->getSize()]);
            $this->uploadAttachment($file, $parentType, $parentId, $userId);
        }

        return ['success' => true];
    }

    private function uploadAttachment(UploadedFileInterface $uploadedFile, string $parentType, int $parentId, int $userId)
    {
        $fileName = $this->attachmentFilePathService->generateFileName();
        $pathName = $this->attachmentFilePathService->generateFilePath($fileName);

        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
            $uploadedFile->moveTo($pathName);
        }

        $attachment = new Attachment();
        $attachment->parent_type = $parentType;
        $attachment->parent_id = $parentId;
        $attachment->submitter_uid = $userId;
        $attachment->client_file_name = $uploadedFile->getClientFilename();
        $attachment->file_name = $fileName;
        $attachment->file_hash = hash_file('md5', $pathName);
        $attachment->file_size = filesize($pathName);
        $attachment->file_mimetype = mime_content_type($pathName);

        $this->attachmentRepository->insert($attachment);
    }
}
