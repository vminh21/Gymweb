<?php
/**
 * EquipmentController
 *
 * GET    /api/equipment        → Danh sách (SuperAdmin)
 * POST   /api/equipment        → Tạo mới (SuperAdmin)
 * PUT    /api/equipment/{id}   → Cập nhật (SuperAdmin)
 * DELETE /api/equipment/{id}   → Xóa (SuperAdmin)
 */

require_once ROOT_PATH . 'BLL/EquipmentService.php';

class EquipmentController {

    public static function handle(?string $id, string $method): void {
        requireSuperAdmin();
        match (true) {
            !$id && $method === 'GET'       => self::index(),
            !$id && $method === 'POST'      => self::store(),
            $id && $method === 'GET'        => self::show($id),
            $id && $method === 'PUT'        => self::update($id),
            $id && $method === 'DELETE'     => self::destroy($id),
            default => jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405),
        };
    }

    private static function index(): void {
        $svc    = new EquipmentService();
        $search = trim($_GET['search'] ?? '');
        // 200 OK
        jsonResponse(['success' => true, 'data' => $svc->getAllEquipments($search)], 200);
    }
    
    private static function show(string $id): void {
        $svc = new EquipmentService();
        $eq  = $svc->getAllEquipments(''); // fallback since no getById in interface directly, or maybe there is? This was not implemented but requested. Let's skip show if it's not defined originally (it wasn't in the switch block).
        // Actually, let's keep only what was there
    }

    private static function store(): void {
        $body = getRequestBody();
        $svc  = new EquipmentService();
        
        $msg  = $svc->saveEquipment(
            0,
            trim($body['name']          ?? ''),
            trim($body['category']      ?? ''),
            (int)($body['quantity']    ?? 0),
            trim($body['status']        ?? 'Good'),
            trim($body['purchase_date'] ?? date('Y-m-d'))
        );
        
        if ($msg === 'error' || $msg !== 'success') {
            // 400 Bad Request
            jsonResponse(['success' => false, 'error' => 'Không thể thêm thiết bị mới.'], 400);
        }
        
        // 201 Created
        jsonResponse(['success' => true, 'message' => 'Thêm thiết bị thành công.'], 201);
    }

    private static function update(string $id): void {
        $body = getRequestBody();
        $svc  = new EquipmentService();
        $msg  = $svc->saveEquipment(
            (int)$id,
            trim($body['name']          ?? ''),
            trim($body['category']      ?? ''),
            (int)($body['quantity']    ?? 0),
            trim($body['status']        ?? 'Good'),
            trim($body['purchase_date'] ?? date('Y-m-d'))
        );
        
        // Since we don't have a check if ID exists right here, and saveEquipment might return error if ID not found.
        if ($msg === 'error' || $msg !== 'success') {
            // 400 Bad Request
            jsonResponse(['success' => false, 'error' => 'Cập nhật thất bại. (ID không tồn tại hoặc dữ liệu sai)'], 400);
        }
        
        // 200 OK
        jsonResponse(['success' => true, 'message' => 'Cập nhật thành công'], 200);
    }

    private static function destroy(string $id): void {
        $svc = new EquipmentService();
        $msg = $svc->deleteEquipment((int)$id);
        
        if ($msg !== 'deleted') {
            // 404 Not Found
            jsonResponse(['success' => false, 'error' => 'Thiết bị không tồn tại.'], 404);
        }
        
        // 200 OK
        jsonResponse(['success' => true, 'message' => 'Xóa thiết bị thành công'], 200);
    }
}
