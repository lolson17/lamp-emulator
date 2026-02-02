<?php
/**
 * AAC Assist API
 * RESTful API for Augmentative and Alternative Communication Application
 *
 * Endpoints:
 *   GET /api.php?action=categories          - Get all categories
 *   GET /api.php?action=phrases&category=X  - Get phrases for category X
 *   GET /api.php?action=phrases             - Get all phrases
 *   GET /api.php?action=search&q=term       - Search phrases by text label
 *   GET /api.php?action=quick_access&user=X - Get quick access phrases for user
 *   POST /api.php?action=track_usage        - Track phrase usage (body: user_id, phrase_id)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/**
 * Database Connection Singleton
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = self::loadConfig();

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['database'],
                $config['charset']
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                self::sendError('Database connection failed: ' . $e->getMessage(), 500);
            }
        }

        return self::$instance;
    }

    private static function loadConfig(): array
    {
        $localConfig = __DIR__ . '/config/database.local.php';
        $defaultConfig = __DIR__ . '/config/database.php';

        if (file_exists($localConfig)) {
            return require $localConfig;
        }

        if (file_exists($defaultConfig)) {
            return require $defaultConfig;
        }

        self::sendError('Database configuration not found', 500);
        exit;
    }

    private static function sendError(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode(['error' => $message, 'code' => $code]);
        exit;
    }
}

/**
 * Category Model
 */
class Category
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all active categories
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, color_code, icon_url, sort_order
             FROM categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, name ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get a single category by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, color_code, icon_url, sort_order
             FROM categories
             WHERE id = ? AND is_active = 1'
        );
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }
}

/**
 * Phrase Model
 */
class Phrase
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all active phrases
     */
    public function getAll(): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, p.text_label, p.speech_output,
                    p.icon_url, p.sort_order, c.name as category_name, c.color_code
             FROM phrases p
             JOIN categories c ON p.category_id = c.id
             WHERE p.is_active = 1 AND c.is_active = 1
             ORDER BY c.sort_order ASC, p.sort_order ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get phrases by category ID
     */
    public function getByCategory(int $categoryId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, p.text_label, p.speech_output,
                    p.icon_url, p.sort_order, c.name as category_name, c.color_code
             FROM phrases p
             JOIN categories c ON p.category_id = c.id
             WHERE p.category_id = ? AND p.is_active = 1 AND c.is_active = 1
             ORDER BY p.sort_order ASC'
        );
        $stmt->execute([$categoryId]);

        return $stmt->fetchAll();
    }

    /**
     * Search phrases by text label using FULLTEXT or LIKE
     */
    public function search(string $query): array
    {
        $query = trim($query);

        if (empty($query)) {
            return [];
        }

        // Sanitize and prepare search term
        $searchTerm = '%' . $query . '%';

        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, p.text_label, p.speech_output,
                    p.icon_url, p.sort_order, c.name as category_name, c.color_code
             FROM phrases p
             JOIN categories c ON p.category_id = c.id
             WHERE (p.text_label LIKE ? OR p.speech_output LIKE ?)
                   AND p.is_active = 1 AND c.is_active = 1
             ORDER BY
                CASE WHEN p.text_label LIKE ? THEN 0 ELSE 1 END,
                p.text_label ASC
             LIMIT 50'
        );

        $exactMatch = $query . '%';
        $stmt->execute([$searchTerm, $searchTerm, $exactMatch]);

        return $stmt->fetchAll();
    }

    /**
     * Get a single phrase by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, p.text_label, p.speech_output,
                    p.icon_url, p.sort_order, c.name as category_name, c.color_code
             FROM phrases p
             JOIN categories c ON p.category_id = c.id
             WHERE p.id = ? AND p.is_active = 1'
        );
        $stmt->execute([$id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }
}

/**
 * Quick Access Model
 */
class QuickAccess
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get quick access phrases for a user
     */
    public function getByUser(int $userId, int $limit = 12): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.category_id, p.text_label, p.speech_output,
                    p.icon_url, c.name as category_name, c.color_code,
                    qa.use_count, qa.last_used
             FROM quick_access qa
             JOIN phrases p ON qa.phrase_id = p.id
             JOIN categories c ON p.category_id = c.id
             WHERE qa.user_id = ? AND p.is_active = 1
             ORDER BY qa.use_count DESC, qa.last_used DESC
             LIMIT ?'
        );
        $stmt->execute([$userId, $limit]);

        return $stmt->fetchAll();
    }

    /**
     * Track phrase usage - increment count or create new entry
     */
    public function trackUsage(int $userId, int $phraseId): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO quick_access (user_id, phrase_id, use_count, last_used)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
                use_count = use_count + 1,
                last_used = NOW()'
        );

        return $stmt->execute([$userId, $phraseId]);
    }
}

/**
 * API Controller
 */
class ApiController
{
    private PDO $db;
    private Category $categoryModel;
    private Phrase $phraseModel;
    private QuickAccess $quickAccessModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->categoryModel = new Category($this->db);
        $this->phraseModel = new Phrase($this->db);
        $this->quickAccessModel = new QuickAccess($this->db);
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'categories':
                    $this->getCategories();
                    break;

                case 'phrases':
                    $this->getPhrases();
                    break;

                case 'search':
                    $this->searchPhrases();
                    break;

                case 'quick_access':
                    $this->getQuickAccess();
                    break;

                case 'track_usage':
                    $this->trackUsage();
                    break;

                case 'categories_with_phrases':
                    $this->getCategoriesWithPhrases();
                    break;

                default:
                    $this->sendResponse([
                        'status' => 'ok',
                        'message' => 'AAC Assist API v1.0',
                        'endpoints' => [
                            'GET categories' => 'Get all categories',
                            'GET phrases' => 'Get all phrases (optional: category=ID)',
                            'GET search' => 'Search phrases (required: q=term)',
                            'GET quick_access' => 'Get quick access phrases (required: user=ID)',
                            'GET categories_with_phrases' => 'Get all categories with their phrases',
                            'POST track_usage' => 'Track phrase usage (body: user_id, phrase_id)',
                        ]
                    ]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getMessage(), 500);
        }
    }

    /**
     * Get all categories
     */
    private function getCategories(): void
    {
        $categories = $this->categoryModel->getAll();
        $this->sendResponse(['categories' => $categories]);
    }

    /**
     * Get phrases (all or by category)
     */
    private function getPhrases(): void
    {
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : null;

        if ($categoryId !== null) {
            $phrases = $this->phraseModel->getByCategory($categoryId);
        } else {
            $phrases = $this->phraseModel->getAll();
        }

        $this->sendResponse(['phrases' => $phrases]);
    }

    /**
     * Search phrases by text label
     */
    private function searchPhrases(): void
    {
        $query = $_GET['q'] ?? '';

        if (empty(trim($query))) {
            $this->sendError('Search query is required', 400);
            return;
        }

        $phrases = $this->phraseModel->search($query);
        $this->sendResponse([
            'query' => $query,
            'count' => count($phrases),
            'phrases' => $phrases
        ]);
    }

    /**
     * Get quick access phrases for a user
     */
    private function getQuickAccess(): void
    {
        $userId = isset($_GET['user']) ? (int)$_GET['user'] : null;

        if ($userId === null || $userId <= 0) {
            $this->sendError('Valid user ID is required', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 12;
        $phrases = $this->quickAccessModel->getByUser($userId, $limit);

        $this->sendResponse(['quick_access' => $phrases]);
    }

    /**
     * Track phrase usage
     */
    private function trackUsage(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError('POST method required', 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $userId = isset($input['user_id']) ? (int)$input['user_id'] : null;
        $phraseId = isset($input['phrase_id']) ? (int)$input['phrase_id'] : null;

        if ($userId === null || $userId <= 0) {
            $this->sendError('Valid user_id is required', 400);
            return;
        }

        if ($phraseId === null || $phraseId <= 0) {
            $this->sendError('Valid phrase_id is required', 400);
            return;
        }

        $success = $this->quickAccessModel->trackUsage($userId, $phraseId);

        $this->sendResponse([
            'success' => $success,
            'message' => $success ? 'Usage tracked successfully' : 'Failed to track usage'
        ]);
    }

    /**
     * Get all categories with their phrases grouped
     */
    private function getCategoriesWithPhrases(): void
    {
        $categories = $this->categoryModel->getAll();
        $result = [];

        foreach ($categories as $category) {
            $category['phrases'] = $this->phraseModel->getByCategory((int)$category['id']);
            $result[] = $category;
        }

        $this->sendResponse(['categories' => $result]);
    }

    /**
     * Send JSON response
     */
    private function sendResponse(array $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Send error response
     */
    private function sendError(string $message, int $code): void
    {
        http_response_code($code);
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $code
        ], JSON_PRETTY_PRINT);
        exit;
    }
}

// Initialize and handle request
$api = new ApiController();
$api->handleRequest();
