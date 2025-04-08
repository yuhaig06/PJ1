<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class WalletModel extends Model
{
    protected $table = 'wallets';
    protected $fillable = [
        'user_id', 'balance'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Tạo ví cho người dùng
     */
    public function createWallet($userId)
    {
        return $this->create([
            'user_id' => $userId,
            'balance' => 0
        ]);
    }

    /**
     * Lấy thông tin ví của người dùng
     */
    public function getUserWallet($userId)
    {
        return $this->findOne(['user_id' => $userId]);
    }

    /**
     * Cập nhật số dư ví
     */
    public function updateBalance($userId, $amount)
    {
        $sql = "UPDATE wallets SET balance = balance + ? WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$amount, $userId]);
    }

    /**
     * Kiểm tra số dư ví
     */
    public function checkBalance($userId, $amount)
    {
        $wallet = $this->getUserWallet($userId);
        return $wallet && $wallet['balance'] >= $amount;
    }

    /**
     * Nạp tiền vào ví
     */
    public function deposit($userId, $amount, $description = null)
    {
        $this->db->beginTransaction();

        try {
            // Cập nhật số dư
            if (!$this->updateBalance($userId, $amount)) {
                throw new \Exception('Không thể cập nhật số dư');
            }

            // Lấy thông tin ví
            $wallet = $this->getUserWallet($userId);
            if (!$wallet) {
                throw new \Exception('Không tìm thấy ví');
            }

            // Tạo giao dịch nạp tiền
            $transaction = [
                'wallet_id' => $wallet['id'],
                'type' => 'deposit',
                'amount' => $amount,
                'balance' => $wallet['balance'],
                'description' => $description,
                'status' => 'completed'
            ];

            if (!$this->createTransaction($transaction)) {
                throw new \Exception('Không thể tạo giao dịch');
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Rút tiền từ ví
     */
    public function withdraw($userId, $amount, $description = null)
    {
        $this->db->beginTransaction();

        try {
            // Kiểm tra số dư
            if (!$this->checkBalance($userId, $amount)) {
                throw new \Exception('Số dư không đủ');
            }

            // Cập nhật số dư
            if (!$this->updateBalance($userId, -$amount)) {
                throw new \Exception('Không thể cập nhật số dư');
            }

            // Lấy thông tin ví
            $wallet = $this->getUserWallet($userId);
            if (!$wallet) {
                throw new \Exception('Không tìm thấy ví');
            }

            // Tạo giao dịch rút tiền
            $transaction = [
                'wallet_id' => $wallet['id'],
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance' => $wallet['balance'],
                'description' => $description,
                'status' => 'completed'
            ];

            if (!$this->createTransaction($transaction)) {
                throw new \Exception('Không thể tạo giao dịch');
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tạo giao dịch ví
     */
    public function createTransaction($data)
    {
        $sql = "INSERT INTO wallet_transactions (wallet_id, type, amount, balance, description, reference_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['wallet_id'],
            $data['type'],
            $data['amount'],
            $data['balance'],
            $data['description'] ?? null,
            $data['reference_id'] ?? null,
            $data['status'] ?? 'pending'
        ]);
    }

    /**
     * Lấy lịch sử giao dịch của ví
     */
    public function getWalletTransactions($userId, $limit = null, $offset = null)
    {
        $sql = "SELECT wt.* 
                FROM wallet_transactions wt 
                JOIN wallets w ON wt.wallet_id = w.id 
                WHERE w.user_id = ? 
                ORDER BY wt.created_at DESC";
        
        $params = [$userId];

        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset !== null) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Lấy thống kê giao dịch
     */
    public function getTransactionStats($userId)
    {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
                    SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
                    SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END) as total_purchases,
                    SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refunds
                FROM wallet_transactions wt 
                JOIN wallets w ON wt.wallet_id = w.id 
                WHERE w.user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Lấy thống kê giao dịch theo thời gian
     */
    public function getTransactionStatsByTime($userId, $startDate, $endDate)
    {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
                    SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
                    SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END) as total_purchases,
                    SUM(CASE WHEN type = 'refund' THEN amount ELSE 0 END) as total_refunds
                FROM wallet_transactions wt 
                JOIN wallets w ON wt.wallet_id = w.id 
                WHERE w.user_id = ? 
                AND created_at BETWEEN ? AND ? 
                GROUP BY DATE(created_at) 
                ORDER BY date";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $startDate, $endDate]);
        return $stmt->fetchAll();
    }
} 