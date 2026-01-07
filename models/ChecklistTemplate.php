<?php
class ChecklistTemplate {
    private $database;
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
     * @param Database $database
     */
    public function __construct(Database $database) {
        $this->database = $database;
    }

    /**
     * Read all checklist templates.
     * @return array
     */
    public function readAll() {
        $query = "SELECT c.*, 
                         i.name as inspection_type_name,
                         i.department
                  FROM " . $this->table_name . " c
                  LEFT JOIN inspection_types i ON c.inspection_type_id = i.id
                  ORDER BY i.name, c.category, c.id";
        
        return $this->database->fetchAll($query);
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
        
        $results = $this->database->fetchAll($query, [$inspection_type_id]);
        
        $templates = [];
        foreach ($results as $row) {
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
        
        $row = $this->database->fetch($query, [$this->id]);

        if ($row) {
            $this->id = $row['id'];
            $this->inspection_type_id = $row['inspection_type_id'];
            $this->category = $row['category'];
            $this->question = $row['question'];
            $this->required = $row['required'];
            $this->input_type = $row['input_type'];
            $this->options = $row['options'];
            $this->created_at = $row['created_at'];
            return $row;
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

        $params = [
            ':inspection_type_id' => $this->inspection_type_id,
            ':category' => htmlspecialchars(strip_tags($this->category)),
            ':question' => htmlspecialchars(strip_tags($this->question)),
            ':required' => $this->required ? 1 : 0,
            ':input_type' => $this->input_type,
            ':options' => !empty($this->options) ? $this->options : null
        ];

        try {
            $this->database->query($query, $params);
            $this->id = $this->database->getConnection()->lastInsertId();
            return true;
        } catch (PDOException $e) {
            error_log("ChecklistTemplate creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing checklist template.
     * @return bool
     */
    public function update() {
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->inspection_type_id !== null) { $fields[] = "inspection_type_id=:inspection_type_id"; $params[':inspection_type_id'] = $this->inspection_type_id; }
        if ($this->category !== null) { $fields[] = "category=:category"; $params[':category'] = htmlspecialchars(strip_tags($this->category)); }
        if ($this->question !== null) { $fields[] = "question=:question"; $params[':question'] = htmlspecialchars(strip_tags($this->question)); }
        if ($this->required !== null) { $fields[] = "required=:required"; $params[':required'] = $this->required ? 1 : 0; }
        if ($this->input_type !== null) { $fields[] = "input_type=:input_type"; $params[':input_type'] = $this->input_type; }
        if ($this->options !== null) { $fields[] = "options=:options"; $params[':options'] = !empty($this->options) ? $this->options : null; }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query($query, $params);
            return true;
        } catch (PDOException $e) {
            error_log("ChecklistTemplate update failed for ID {$this->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a checklist template.
     * @return bool
     */
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        try {
            $this->database->query($query, [$this->id]);
            return true;
        } catch (PDOException $e) {
            error_log("ChecklistTemplate deletion failed: " . $e->getMessage());
            return false;
        }
    }
}
?>