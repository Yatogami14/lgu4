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
        // Step 1: Fetch all checklist templates from the checklist database.
        $query = "SELECT *
                  FROM " . $this->table_name . "
                  ORDER BY inspection_type_id, category, id";
        $templates = $this->database->fetchAll(Database::DB_CHECKLIST, $query);

        if (empty($templates)) {
            return [];
        }

        // Step 2: Collect all unique inspection_type_ids.
        $inspection_type_ids = array_unique(array_column($templates, 'inspection_type_id'));

        // Step 3: Fetch the inspection type names from the core database.
        $inspection_types = [];
        if (!empty($inspection_type_ids)) {
            $in_clause = implode(',', array_fill(0, count($inspection_type_ids), '?'));
            $types_data = $this->database->fetchAll(Database::DB_CORE, "SELECT id, name FROM inspection_types WHERE id IN ($in_clause)", $inspection_type_ids);
            foreach ($types_data as $type) {
                $inspection_types[$type['id']] = $type['name'];
            }
        }

        // Step 4: Combine the data in PHP.
        foreach ($templates as &$template) {
            $template['inspection_type_name'] = $inspection_types[$template['inspection_type_id']] ?? 'N/A';
        }

        return $templates;
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
        
        $results = $this->database->fetchAll(Database::DB_CHECKLIST, $query, [$inspection_type_id]);
        
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
        
        $row = $this->database->fetch(Database::DB_CHECKLIST, $query, [$this->id]);

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

        $pdo = $this->database->getConnection(Database::DB_CHECKLIST);
        $stmt = $pdo->prepare($query);

        // Bind
        $stmt->bindParam(":inspection_type_id", $this->inspection_type_id);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":question", $this->question);
        $required = $this->required ? 1 : 0;
        $stmt->bindParam(":required", $required, PDO::PARAM_INT);
        $stmt->bindParam(":input_type", $this->input_type);
        $options = !empty($this->options) ? $this->options : null;
        $stmt->bindParam(":options", $options);

        if ($stmt->execute()) {
            $this->id = $pdo->lastInsertId();
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
        $fields = [];
        $params = [':id' => $this->id];

        if ($this->inspection_type_id !== null) { $fields[] = "inspection_type_id=:inspection_type_id"; $params[':inspection_type_id'] = $this->inspection_type_id; }
        if ($this->category !== null) { $fields[] = "category=:category"; $params[':category'] = $this->category; }
        if ($this->question !== null) { $fields[] = "question=:question"; $params[':question'] = $this->question; }
        if ($this->required !== null) { $fields[] = "required=:required"; $params[':required'] = $this->required ? 1 : 0; }
        if ($this->input_type !== null) { $fields[] = "input_type=:input_type"; $params[':input_type'] = $this->input_type; }
        if ($this->options !== null) { $fields[] = "options=:options"; $params[':options'] = !empty($this->options) ? $this->options : null; }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $query = "UPDATE " . $this->table_name . " SET " . implode(', ', $fields) . " WHERE id=:id";

        try {
            $this->database->query(Database::DB_CHECKLIST, $query, $params);
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
        $pdo = $this->database->getConnection(Database::DB_CHECKLIST);
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(1, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("ChecklistTemplate deletion failed: " . implode(";", $stmt->errorInfo()));
        return false;
    }
}
?>