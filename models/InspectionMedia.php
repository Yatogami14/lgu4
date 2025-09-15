<?php
class InspectionMedia {
    private $conn;
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

    public function __construct($db) {
        $this->conn = $db;
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

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->inspection_id = htmlspecialchars(strip_tags($this->inspection_id));
        $this->uploaded_by = htmlspecialchars(strip_tags($this->uploaded_by));
        $this->file_path = htmlspecialchars(strip_tags($this->file_path));
        $this->filename = htmlspecialchars(strip_tags($this->filename));
        $this->file_type = htmlspecialchars(strip_tags($this->file_type));
        $this->file_size = htmlspecialchars(strip_tags($this->file_size));

        // Bind
        $stmt->bindParam(":inspection_id", $this->inspection_id);
        $stmt->bindParam(":uploaded_by", $this->uploaded_by);
        $stmt->bindParam(":file_path", $this->file_path);
        $stmt->bindParam(":filename", $this->filename);
        $stmt->bindParam(":file_type", $this->file_type);
        $stmt->bindParam(":file_size", $this->file_size);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    public function updateAiAnalysis() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    ai_analysis = :ai_analysis
                WHERE
                    id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        // The analysis is JSON, so we just pass it through. htmlspecialchars would corrupt it.
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(":ai_analysis", $this->ai_analysis);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }

        error_log("Failed to update AI analysis for media ID: " . $this->id);
        return false;
    }

    public function readByInspectionId($inspection_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE inspection_id = ? 
                  ORDER BY created_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $inspection_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>