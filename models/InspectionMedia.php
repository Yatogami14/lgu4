<?php
class InspectionMedia {
    private $database;
    private $table_name = "inspection_media";

    public $id;
    public $inspection_id;
    public $uploaded_by;
    public $file_path;
    public $filename;
    public $file_type;
    public $ai_analysis;
    public $file_size;
    public $created_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    inspection_id = :inspection_id,
                    uploaded_by = :uploaded_by,
                    file_path = :file_path,
                    filename = :filename,
                    file_type = :file_type,
                    file_size = :file_size";

        $params = [
            ":inspection_id" => $this->inspection_id,
            ":uploaded_by" => $this->uploaded_by,
            ":file_path" => $this->file_path,
            ":filename" => $this->filename,
            ":file_type" => $this->file_type,
            ":file_size" => $this->file_size,
        ];

        try {
            $pdo = $this->database->getConnection(Database::DB_MEDIA);
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $this->id = $pdo->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("InspectionMedia creation failed: " . $e->getMessage());
            return false;
        }
    }

    public function updateAiAnalysis() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    ai_analysis = :ai_analysis
                WHERE
                    id = :id";

        $params = [
            ":ai_analysis" => $this->ai_analysis,
            ":id" => $this->id,
        ];

        try {
            $this->database->query(Database::DB_MEDIA, $query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update AI analysis for media ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    public function readByInspectionId($inspection_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE inspection_id = ? 
                  ORDER BY created_at ASC";

        return $this->database->query(Database::DB_MEDIA, $query, [$inspection_id]);
    }
}
?>