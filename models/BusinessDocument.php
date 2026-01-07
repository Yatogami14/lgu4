<?php
class BusinessDocument {
    private $database;
    private $table_name = "business_documents";

    public $id;
    public $business_id;
    public $document_type;
    public $file_path;
    public $file_name;
    public $mime_type;
    public $file_size;
    public $status;
    public $feedback;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET business_id=:business_id, document_type=:document_type, file_path=:file_path,
                      file_name=:file_name, mime_type=:mime_type, file_size=:file_size";

        $params = [
            ':business_id' => $this->business_id,
            ':document_type' => $this->document_type,
            ':file_path' => $this->file_path,
            ':file_name' => $this->file_name,
            ':mime_type' => $this->mime_type,
            ':file_size' => $this->file_size
        ];

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("BusinessDocument creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function readByBusinessId($business_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE business_id = ?";
        return $this->database->fetchAll($query, [$business_id]);
    }

    public function deleteByBusinessId($business_id) {
        // First, get all file paths to delete them from the server
        $select_query = "SELECT file_path FROM " . $this->table_name . " WHERE business_id = ?";
        $files_to_delete = $this->database->fetchAll($select_query, [$business_id]);
        
        foreach ($files_to_delete as $row) {
            // Assumes the script is run from a directory one level below the root
            $file_to_delete = '../' . $row['file_path'];
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
        }

        // Then, delete the records from the database
        $delete_query = "DELETE FROM " . $this->table_name . " WHERE business_id = ?";
        
        try {
            $this->database->query($delete_query, [$business_id]);
            return true;
        } catch (PDOException $e) {
            error_log("BusinessDocument deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function deleteByBusinessIdAndType($business_id, $document_type) {
        // Get file path to delete from server
        $select_query = "SELECT file_path FROM " . $this->table_name . " WHERE business_id = ? AND document_type = ?";
        $row = $this->database->fetch($select_query, [$business_id, $document_type]);
        
        if ($row) {
            $file_to_delete = '../' . $row['file_path'];
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
        }

        $delete_query = "DELETE FROM " . $this->table_name . " WHERE business_id = ? AND document_type = ?";
        try {
            $this->database->query($delete_query, [$business_id, $document_type]);
            return true;
        } catch (PDOException $e) {
            error_log("BusinessDocument deletion failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateStatus($id, $status, $feedback = null) {
        $query = "UPDATE " . $this->table_name . " SET status = :status, feedback = :feedback WHERE id = :id";
        $params = [':status' => $status, ':feedback' => $feedback, ':id' => $id];
        return $this->database->query($query, $params);
    }
}
?>