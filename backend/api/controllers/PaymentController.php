<?php
/**
 * PaymentController
 *
 * Chuẩn RESTful API: 
 * POST /api/payments -> Khởi tạo thanh toán hoặc xác nhận (dựa vào payload)
 */

require_once ROOT_PATH . 'BLL/PaymentService.php';

class PaymentController {

    public static function handle(?string $id, ?string $sub, string $method): void {
        // Chỉ chấp nhận POST /api/payments (Không có id hay verb lẫn vào URL)
        if ($method === 'POST' && empty($id)) {
            self::processPaymentRequest();
            return;
        }
        jsonResponse(['success' => false, 'error' => 'Endpoint Not Found or Method Not Allowed'], 404);
    }

    private static function processPaymentRequest(): void {
        try {
            $payload   = requireMember();
            $memberId  = (int)$payload['member_id'];
            $body      = getRequestBody();

            $packageId     = (int)($body['package_id'] ?? 0);
            $trainerId     = !empty($body['trainer_id']) ? (int)$body['trainer_id'] : null;
            $courseName    = !empty($body['course_name']) ? trim($body['course_name']) : null;
            $paymentMethod = trim($body['payment_method'] ?? 'Tiền mặt');
            $ignoreActive  = !empty($body['ignore_active']);
            $action        = $body['action'] ?? 'initiate'; // Thêm trường action để phân biệt

            if ($packageId <= 0) {
                jsonResponse(['success' => false, 'error' => 'Thiếu thông tin gói tập hợp lệ.'], 400);
            }

            $svc = new PaymentService();

            // Gọi Service kiểm tra điều kiện (BLL)
            $svc->checkEligibleForNewPackage($memberId, $ignoreActive);

            if ($paymentMethod === 'Tiền mặt') {
                $svc->activatePackage($memberId, $packageId, 'Tiền mặt', $trainerId, $courseName);
                jsonResponse(['success' => true, 'message' => 'Đăng ký thành công! Vui lòng thanh toán tại quầy.'], 201);
            }

            if ($paymentMethod === 'Chuyển khoản') {
                if ($action === 'confirm') {
                    // Xác nhận chuyển khoản (Frontend gửi thêm action: "confirm")
                    $svc->activatePackage($memberId, $packageId, 'Chuyển khoản', $trainerId, $courseName);
                    jsonResponse(['success' => true, 'message' => 'Xác nhận chuyển khoản thành công! Gói tập đã kích hoạt.'], 201);
                } else {
                    // Khởi tạo chuyển khoản
                    $data = $svc->generateTransferInfo($memberId, $packageId);
                    $data['success'] = true;
                    $data['package_id'] = $packageId;
                    $data['trainer_id'] = $trainerId;
                    $data['course_name'] = $courseName;
                    jsonResponse($data, 200);
                }
            }

            if ($paymentMethod === 'Momo') {
                // Trích xuất biến môi trường để truyền vào Service
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $hostUrl = $protocol . $host;
                $frontendUrl = $protocol . "localhost:5173"; // Lấy từ env trong thực tế

                $data = $svc->createMoMoPayment($memberId, $packageId, $hostUrl, $frontendUrl, $trainerId, $courseName);
                jsonResponse(['success' => true, 'method' => 'momo', 'payUrl' => $data['payUrl']], 200);
            }

            jsonResponse(['success' => false, 'error' => 'Phương thức thanh toán không hợp lệ'], 400);

        } catch (Exception $e) {
            // Controller đóng vai trò gom lỗi từ BLL để trả mã HTTP Code chuẩn REST
            $statusCode = $e->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 500; // Mặc định lỗi server nếu code không chuẩn
            }
            jsonResponse(['success' => false, 'error' => $e->getMessage()], $statusCode);
        }
    }
}
?>đã kích hoạt.']);
        }
        jsonResponse(['success' => false, 'error' => $result['error'] ?? 'Lỗi kích hoạt gói.'], 422);
    }
}
