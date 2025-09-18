<?php
class InspectionMedia {
    private $database;
    private $table_name = "media";

    public $id;
    public $related_entity_id; // Was inspection_id
    public $related_entity_type = 'inspection';
    public $uploader_id; // Was uploaded_by
    public $file_path;
    public $file_name; // Was filename
    public $mime_type; // Was file_type
    public $ai_analysis;
    public $file_size;
    public $created_at;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    related_entity_id = :related_entity_id,
                    related_entity_type = :related_entity_type,
                    uploader_id = :uploader_id,
                    file_path = :file_path,
                    file_name = :file_name,
                    mime_type = :mime_type,
                    file_size = :file_size";

        $params = [
            ":related_entity_id" => $this->related_entity_id,
            ":related_entity_type" => $this->related_entity_type,
            ":uploader_id" => $this->uploader_id,
            ":file_path" => $this->file_path,
            ":file_name" => $this->file_name,
            ":mime_type" => $this->mime_type,
            ":file_size" => $this->file_size,
        ];

        try {
            $pdo = $this->database->getConnection();
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
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("Failed to update AI analysis for media ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    public function readByInspectionId($inspection_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE related_entity_id = ? AND related_entity_type = 'inspection'
                  ORDER BY created_at ASC";

        return $this->database->fetchAll($query, [$inspection_id]);
    }
}
?>