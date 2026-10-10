<?php

declare(strict_types=1);

namespace App\Modules\Attachments\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

final class AttachmentsController extends BaseController
{
    public function storePreorder(int $id)
    {
        return $this->store('preorder', $id, '/pre-vendas/' . $id);
    }

    public function storeGarage(int $id)
    {
        return $this->store('garage', $id, '/garagem/' . $id);
    }

    public function download(int $id)
    {
        $attachment = db_connect()->table('attachments')->where(['id' => $id, 'tenant_id' => $this->tenant()])->get()->getRowArray();
        $path = $attachment ? WRITEPATH . 'uploads/' . $attachment['path'] : '';
        if (! $attachment || ! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null)->setFileName($attachment['original_name']);
    }

    private function store(string $type, int $id, string $returnTo)
    {
        if (! $this->validate(['attachment' => 'uploaded[attachment]|max_size[attachment,5120]|mime_in[attachment,image/jpeg,image/png,image/webp,application/pdf]'])) {
            return redirect()->to($returnTo)->with('errors', $this->validator->getErrors());
        }
        $table = $type === 'preorder' ? 'preorders' : 'garage_items';
        $tenantId = $this->tenant();
        if (db_connect()->table($table)->where(['id' => $id, 'tenant_id' => $tenantId])->countAllResults() === 0) {
            throw PageNotFoundException::forPageNotFound();
        }
        $file = $this->request->getFile('attachment');
        $directory = WRITEPATH . 'uploads/attachments/' . $tenantId;
        if (! is_dir($directory)) {
            mkdir($directory, 0700, true);
        }
        $filename = $file->getRandomName();
        $file->move($directory, $filename);
        $now = gmdate('Y-m-d H:i:s');
        db_connect()->table('attachments')->insert([
            'tenant_id' => $tenantId,
            'owner_type' => $type,
            'owner_id' => $id,
            'path' => 'attachments/' . $tenantId . '/' . $filename,
            'original_name' => $file->getClientName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return redirect()->to($returnTo)->with('message', 'Arquivo anexado com sucesso.');
    }

    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }
}
