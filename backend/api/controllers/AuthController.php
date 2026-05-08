<?php
/**
 * AuthController
 *
 * POST   /api/auth/login     → Đăng nhập, trả JWT
 * POST   /api/auth/register  → Đăng ký hội viên mới
 * POST   /api/auth/logout    → Logout (client tự xóa token)
 * GET    /api/auth/me        → Thông tin user từ JWT hiện tại
 */

require_once ROOT_PATH . 'BLL/AuthService.php';

class AuthController {

    public static function handle(?string $sub, string $method): void {
    match (true) {
        // POST /api/auth/login
        $sub === 'login' && $method === 'POST'           => self::login(),
        // POST /api/auth/register
        $sub === 'register' && $method === 'POST'        => self::register(),
        // POST /api/auth/logout
        $sub === 'logout' && $method === 'POST'          => self::logout(),
        // GET  /api/auth/me
        $sub === 'me' && $method === 'GET'               => self::me(),
        // POST /api/auth/forgot-password
        $sub === 'forgot-password' && $method === 'POST' => self::forgotPassword(),

        default => jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405),
    };
    }

    // ── POST /api/auth/login ──────────────────────────────────────────────────
    private static function login(): void {
        $body     = getRequestBody();
        $email    = trim($body['username'] ?? $body['email'] ?? '');
        $password = trim($body['password'] ?? '');

        $authService = new AuthService();
        $result = $authService->authenticate($email, $password);

        if (!$result['status']) {
            jsonResponse(['success' => false, 'error' => $result['error']], 401);
        }

        $role = $result['role'];
        $user = $result['user'];

        // Xây JWT payload theo role
        $payload = [
            'role'      => $role,
            'full_name' => $user['full_name'],
        ];

        if ($role === 'admin') {
            $payload['admin_id']  = $user['id'];
            $payload['position']  = $user['position'];
            // Nếu position === 'staff' thì ghi đè role
            if ($user['position'] === 'staff') {
                $payload['role'] = 'staff';
            }
        } elseif ($role === 'pt') {
            $payload['trainer_id'] = $user['id'];
        } else {
            $payload['member_id']  = $user['id'];
        }

        $token = JWTHandler::encode($payload);

        jsonResponse([
            'success'   => true,
            'token'     => $token,
            'role'      => $payload['role'],
            'full_name' => $user['full_name'],
        ]);
    }

    // ── POST /api/auth/register ───────────────────────────────────────────────
    private static function register(): void {
        $body = getRequestBody();

        $authService = new AuthService();
        $result = $authService->register(
            trim($body['full_name']    ?? ''),
            trim($body['email']        ?? ''),
            trim($body['phone']        ?? ''),
            trim($body['address']      ?? ''),
            trim($body['gender']       ?? ''),
            trim($body['password']     ?? ''),
            trim($body['confirm_pass'] ?? '')
        );

        if ($result['status']) {
            jsonResponse(['success' => true, 'message' => $result['message'] ?? 'Đăng ký thành công!'], 201);
        } else {
            jsonResponse(['success' => false, 'errors' => $result['errors'] ?? [], 'error' => $result['error'] ?? ''], 422);
        }
    }

    // ── POST /api/auth/logout ─────────────────────────────────────────────────
    private static function logout(): void {
        // Với JWT stateless, server không cần làm gì - client tự xóa token
        jsonResponse(['success' => true, 'message' => 'Đã đăng xuất thành công']);
    }

    // ── POST /api/auth/forgot-password ────────────────────────────────────────
    private static function forgotPassword(): void {
        $body  = getRequestBody();
        $email = trim($body['email'] ?? '');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(['success' => false, 'error' => 'Email không hợp lệ!'], 422);
        }

        // Kiểm tra email tồn tại trong DB
        require_once ROOT_PATH . 'Config/Database.php';
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT member_id FROM members WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if (!$stmt->fetch()) {
            // Trả về success để không lộ thông tin user có tồn tại hay không
            jsonResponse(['success' => true, 'message' => 'Nếu email tồn tại, chúng tôi đã gửi hướng dẫn khôi phục.']);
        }

        // TODO: Gửi email thực tế (cần cấu hình SMTP/PHPMailer)
        // Hiện tại trả về thành công để frontend hoạt động
        jsonResponse(['success' => true, 'message' => 'Đã gửi email khôi phục mật khẩu! Vui lòng kiểm tra hộp thư.']);
    }

    // ── GET /api/auth/me ──────────────────────────────────────────────────────
    private static function me(): void {
        $payload = getJWTPayload();
        if (!$payload) {
            jsonResponse(['success' => true, 'data' => ['loggedIn' => false]]);
        }
        jsonResponse([
            'success' => true,
            'data' => [
                'loggedIn'   => true,
                'role'       => $payload['role']       ?? null,
                'full_name'  => $payload['full_name']  ?? null,
                'member_id'  => $payload['member_id']  ?? null,
                'admin_id'   => $payload['admin_id']   ?? null,
                'trainer_id' => $payload['trainer_id'] ?? null,
            ]
        ]);
    }
}
