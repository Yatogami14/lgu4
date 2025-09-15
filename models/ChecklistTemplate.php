<?php
class ChecklistTemplate {
    private $conn;
    private $table_name = "checklist_templates";

    // Object properties
    public $id;
    public $inspection_type_id;
    public $category;
    public $question;
    public $required;
    public $input_type;
    public $options; // This will be a JSON string
    public $created_at;

    /**
     * Constructor with database connection.
     * @param PDO $db
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Read all checklist templates.
     * @return PDOStatement
     */
    public function readAll() {
        $query = "SELECT ct.*, it.name as inspection_type_name
                  FROM " . $this->table_name . " ct
                  LEFT JOIN " . Database::DB_CORE . ".inspection_types it ON ct.inspection_type_id = it.id
                  ORDER BY ct.inspection_type_id, ct.category, ct.id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Read templates by inspection type, grouped by category.
     * This is the primary method for fetching a checklist for a form.
     * @param int $inspection_type_id
     * @return array
     */
    public function readByInspectionType($inspection_type_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE inspection_type_id = ? 
                  ORDER BY category, id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $inspection_type_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $templates = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Decode JSON options string into an array for easier use in the view
            if (!empty($row['options'])) {
                $row['options'] = json_decode($row['options'], true);
            }
            // Group by category
            $templates[$row['category']][] = $row;
        }
        return $templates;
    }

    /**
     * Read a single checklist template by ID.
     * @return bool
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id'];
            $this->inspection_type_id = $row['inspection_type_id'];
            $this->category = $row['category'];
            $this->question = $row['question'];
            $this->required = $row['required'];
            $this->input_type = $row['input_type'];
            $this->options = $row['options'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    /**
     * Create a new checklist template.
     * @return bool
     */
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET inspection_type_id=:inspection_type_id, category=:category, question=:question, 
                      required=:required, input_type=:input_type, options=:options";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->inspection_type_id = htmlspecialchars(strip_tags($this->inspection_type_id));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->question = htmlspecialchars(strip_tags($this->question));
        $this->required = $this->required ? 1 : 0;
        $this->input_type = htmlspecialchars(strip_tags($this->input_type));
        // The 'options' property is expected to be a valid JSON string or null.
        $this->options = !empty($this->options) ? $this->options : null;

        // Bind
        $stmt->bindParam(":inspection_type_id", $this->inspection_type_id);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":question", $this->question);
        $stmt->bindParam(":required", $this->required, PDO::PARAM_INT);
        $stmt->bindParam(":input_type", $this->input_type);
        $stmt->bindParam(":options", $this->options);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        error_log("ChecklistTemplate creation failed: " . implode(";", $stmt->errorInfo()));
        return false;
    }

    /**
     * Update an existing checklist template.
     * @return bool
     */
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET inspection_type_id=:inspection_type_id, category=:category, question=:question, 
                      required=:required, input_type=:input_type, options=:options
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->inspection_type_id = htmlspecialchars(strip_tags($this->inspection_type_id));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->question = htmlspecialchars(strip_tags($this->question));
        $this->required = $this->required ? 1 : 0;
        $this->input_type = htmlspecialchars(strip_tags($this->input_type));
        $this->options = !empty($this->options) ? $this->options : null;

        // Bind
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":inspection_type_id", $this->inspection_type_id);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":question", $this->question);
        $stmt->bindParam(":required", $this->required, PDO::PARAM_INT);
        $stmt->bindParam(":input_type", $this->input_type);
        $stmt->bindParam(":options", $this->options);

        if ($stmt->execute()) {
            return true;
        }
        error_log("ChecklistTemplate update failed: " . implode(";", $stmt->errorInfo()));
        return false;
    }

    /**
     * Delete a checklist template.
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("ChecklistTemplate deletion failed: " . implode(";", $stmt->errorInfo()));
        return false;
    }
}
?>