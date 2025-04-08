<?php
class SettingModel {
    private $db;

    public function __construct() {
        $this->db = new Database;
    }

    // Lấy tất cả cài đặt
    public function getAllSettings() {
        $this->db->query('SELECT * FROM settings ORDER BY setting_group, setting_key');
        return $this->db->resultSet();
    }

    // Lấy cài đặt theo nhóm
    public function getSettingsByGroup($group) {
        $this->db->query('SELECT * FROM settings WHERE setting_group = :group ORDER BY setting_key');
        $this->db->bind(':group', $group);
        return $this->db->resultSet();
    }

    // Lấy giá trị cài đặt theo key
    public function getSettingValue($key) {
        $this->db->query('SELECT setting_value FROM settings WHERE setting_key = :key');
        $this->db->bind(':key', $key);
        $result = $this->db->single();
        
        return $result ? $result->setting_value : null;
    }

    // Cập nhật giá trị cài đặt
    public function updateSetting($key, $value) {
        $this->db->query('UPDATE settings SET setting_value = :value WHERE setting_key = :key');
        $this->db->bind(':value', $value);
        $this->db->bind(':key', $key);
        
        return $this->db->execute();
    }

    // Thêm cài đặt mới
    public function addSetting($data) {
        $this->db->query('INSERT INTO settings (setting_key, setting_value, setting_group) 
                         VALUES (:key, :value, :group)');
        
        $this->db->bind(':key', $data['key']);
        $this->db->bind(':value', $data['value']);
        $this->db->bind(':group', $data['group']);
        
        return $this->db->execute();
    }

    // Xóa cài đặt
    public function deleteSetting($key) {
        $this->db->query('DELETE FROM settings WHERE setting_key = :key');
        $this->db->bind(':key', $key);
        
        return $this->db->execute();
    }

    // Lấy danh sách các nhóm cài đặt
    public function getSettingGroups() {
        $this->db->query('SELECT DISTINCT setting_group FROM settings ORDER BY setting_group');
        return $this->db->resultSet();
    }
} 