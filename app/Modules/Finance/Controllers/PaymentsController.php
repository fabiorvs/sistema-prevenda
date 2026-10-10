<?php

namespace App\Modules\Finance\Controllers;

use App\Controllers\BaseController;
use App\Modules\Tenancy\Services\ActiveTenantResolver;
use CodeIgniter\Exceptions\PageNotFoundException;

class PaymentsController extends BaseController
{
    private function tenant(): int
    {
        return (new ActiveTenantResolver(db_connect(), session()))->forUser((int) auth()->user()->id);
    }

    public function create(int $entryId): string
    {
        return view('finance/payment_form', ['entry' => $this->entry($entryId)]);
    }

    public function store(int $entryId)
    {
        $rules = [
            'amount' => 'required|decimal|greater_than[0]',
            'method' => 'required|in_list[pix,transferencia,cartao,dinheiro,outro]',
            'proof' => 'if_exist|max_size[proof,5120]|mime_in[proof,image/jpeg,image/png,image/webp,application/pdf]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $entry = $this->entry($entryId);
        $amount = $this->money($this->request->getPost('amount'));
        $db = db_connect();
        $allocated = (float) ($db->table('payment_allocations')->selectSum('amount')->where('financial_entry_id', $entry['id'])->get()->getRowArray()['amount'] ?? 0);
        $remaining = (float) $entry['amount'] - $allocated;

        if ($amount > $remaining + 0.0001) {
            return redirect()->back()->withInput()->with('errors', ['amount' => 'O pagamento excede o saldo da conta: R$ ' . number_format($remaining, 2, ',', '.') . '.']);
        }

        $proofPath = null;
        $file = $this->request->getFile('proof');
        if ($file && $file->isValid() && ! $file->hasMoved()) {
            $directory = WRITEPATH . 'uploads/proofs/' . $this->tenant();
            if (! is_dir($directory)) {
                mkdir($directory, 0700, true);
            }
            $filename = $file->getRandomName();
            $file->move($directory, $filename);
            $proofPath = 'proofs/' . $this->tenant() . '/' . $filename;
        }

        $db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('payments')->insert([
            'tenant_id' => $this->tenant(),
            'preorder_id' => $entry['preorder_id'],
            'amount' => number_format($amount, 2, '.', ''),
            'type' => 'payment',
            'category' => 'preorder',
            'method' => $this->request->getPost('method'),
            'status' => 'confirmed',
            'paid_at' => $now,
            'proof_path' => $proofPath,
            'idempotency_key' => bin2hex(random_bytes(16)),
            'notes' => $this->request->getPost('notes'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $paymentId = (int) $db->insertID();
        $db->table('payment_allocations')->insert([
            'tenant_id' => $this->tenant(),
            'payment_id' => $paymentId,
            'financial_entry_id' => $entry['id'],
            'amount' => number_format($amount, 2, '.', ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $newStatus = ($amount + $allocated) >= (float) $entry['amount'] ? 'paid' : 'partial';
        $db->table('financial_entries')->where('id', $entry['id'])->update(['status' => $newStatus, 'updated_at' => $now]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível registrar o pagamento.');
        }

        return redirect()->to('/financeiro')->with('message', 'Pagamento registrado.');
    }

    public function refund(int $paymentId)
    {
        $db = db_connect();
        $payment = $db->table('payments')->where(['id' => $paymentId, 'tenant_id' => $this->tenant(), 'type' => 'payment', 'status' => 'confirmed'])->get()->getRowArray();
        if (! $payment) {
            throw PageNotFoundException::forPageNotFound();
        }

        $alreadyRefunded = $db->table('payments')->where(['tenant_id' => $this->tenant(), 'type' => 'refund', 'notes' => 'Estorno do pagamento #' . $paymentId])->countAllResults();
        if ($alreadyRefunded > 0) {
            return redirect()->to('/financeiro/pagamentos')->with('error', 'Este pagamento já foi estornado.');
        }

        $db->transStart();
        $now = gmdate('Y-m-d H:i:s');
        $db->table('payments')->insert([
            'tenant_id' => $this->tenant(), 'preorder_id' => $payment['preorder_id'], 'amount' => $payment['amount'], 'type' => 'refund', 'category' => $payment['category'], 'method' => $payment['method'], 'status' => 'confirmed', 'paid_at' => $now, 'idempotency_key' => bin2hex(random_bytes(16)), 'notes' => 'Estorno do pagamento #' . $paymentId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $refundId = (int) $db->insertID();
        $allocations = $db->table('payment_allocations')->where('payment_id', $paymentId)->get()->getResultArray();
        foreach ($allocations as $allocation) {
            $db->table('payment_allocations')->insert([
                'tenant_id' => $this->tenant(),
                'payment_id' => $refundId,
                'financial_entry_id' => $allocation['financial_entry_id'],
                'amount' => -1 * (float) $allocation['amount'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $db->table('financial_entries')->where('id', $allocation['financial_entry_id'])->update(['status' => 'pending', 'updated_at' => $now]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Não foi possível estornar o pagamento.');
        }

        return redirect()->to('/financeiro/pagamentos')->with('message', 'Pagamento estornado.');
    }

    private function entry(int $id): array
    {
        $entry = db_connect()->table('financial_entries')->where(['id' => $id, 'tenant_id' => $this->tenant()])->get()->getRowArray();
        if (! $entry) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $entry;
    }

    private function money($value): float
    {
        return round(max(0, (float) str_replace(',', '.', (string) $value)), 2);
    }
}
