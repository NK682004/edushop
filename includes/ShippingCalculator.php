<?php
/**
 * Class tính phí vận chuyển
 */
class ShippingCalculator {
    private $db;
    
    // Cấu hình phí ship mặc định (khớp với cart: miễn phí từ 200k)
    private $defaultConfig = [
        [
            'min' => 0,
            'max' => 199999,
            'fee' => 30000,
            'free_threshold' => 200000
        ],
        [
            'min' => 200000,
            'max' => null,
            'fee' => 0,
            'free_threshold' => null
        ]
    ];
    
    /**
     * Kiểm tra xem có đủ điều kiện miễn phí ship không
     * Dựa vào database config
     */
    private function checkFreeShipping($subtotal, $config) {
        // Nếu có free_shipping_threshold và đạt ngưỡng → miễn phí
        if ($config['free_shipping_threshold'] && $subtotal >= $config['free_shipping_threshold']) {
            return 0; // Miễn phí
        }
        // Nếu không → trả về phí bình thường
        return $config['fee'];
    }
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Tính phí vận chuyển dựa trên giá trị đơn hàng
     * 
     * @param float $subtotal Giá trị đơn hàng (chưa gồm ship)
     * @param string $region Khu vực giao hàng (tùy chọn)
     * @return array ['fee' => phí ship, 'message' => thông báo]
     */
    public function calculate($subtotal, $region = 'default') {
        // Thử lấy từ database nếu có
        if ($this->db) {
            $config = $this->getConfigFromDB($subtotal, $region);
            if ($config) {
                return [
                    'fee' => $config['fee'],
                    'message' => $this->getMessage($config['fee'], $subtotal, $config['free_shipping_threshold']),
                    'free_shipping_threshold' => $config['free_shipping_threshold']
                ];
            }
        }
        
        // Dùng cấu hình mặc định
        return $this->calculateDefault($subtotal);
    }
    
    /**
     * Tính phí ship theo cấu hình mặc định
     */
    private function calculateDefault($subtotal) {
        foreach ($this->defaultConfig as $tier) {
            if ($subtotal >= $tier['min'] && ($tier['max'] === null || $subtotal <= $tier['max'])) {
                return [
                    'fee' => $tier['fee'],
                    'message' => $this->getMessage($tier['fee'], $subtotal, $tier['free_threshold']),
                    'free_shipping_threshold' => $tier['free_threshold']
                ];
            }
        }
        
        // Mặc định
        return [
            'fee' => 30000,
            'message' => 'Phí vận chuyển tiêu chuẩn',
            'free_shipping_threshold' => 500000
        ];
    }
    
    /**
     * Lấy cấu hình từ database
     */
    private function getConfigFromDB($subtotal, $region) {
        try {
            $stmt = $this->db->prepare("
                SELECT fee, free_shipping_threshold 
                FROM shipping_configs 
                WHERE region = ? 
                AND is_active = 1
                AND min_order <= ?
                AND (max_order IS NULL OR max_order >= ?)
                ORDER BY min_order DESC
                LIMIT 1
            ");
            $stmt->execute([$region, $subtotal, $subtotal]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Kiểm tra miễn phí ship
            if ($config && $config['free_shipping_threshold'] && $subtotal >= $config['free_shipping_threshold']) {
                $config['fee'] = 0; // Override thành miễn phí
            }
            
            return $config;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Tạo thông báo về phí ship
     */
    private function getMessage($fee, $subtotal, $freeThreshold) {
        if ($fee == 0) {
            return '🎉 Miễn phí vận chuyển!';
        }
        
        if ($freeThreshold && $subtotal < $freeThreshold) {
            $remaining = $freeThreshold - $subtotal;
            return '📦 Phí ship: ' . number_format($fee) . 'đ (Mua thêm ' . number_format($remaining) . 'đ để được miễn phí ship)';
        }
        
        return '📦 Phí vận chuyển: ' . number_format($fee) . 'đ';
    }
    
    /**
     * Kiểm tra miễn phí ship
     */
    public function isFreeShipping($subtotal) {
        $result = $this->calculate($subtotal);
        return $result['fee'] == 0;
    }
    
    /**
     * Tính tổng đơn hàng
     */
    public function calculateTotal($subtotal, $discount = 0, $customShippingFee = null) {
        $shippingInfo = $this->calculate($subtotal);
        $shippingFee = $customShippingFee !== null ? $customShippingFee : $shippingInfo['fee'];
        
        $total = $subtotal + $shippingFee - $discount;
        
        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'discount' => $discount,
            'total' => max(0, $total), // Không âm
            'shipping_message' => $shippingInfo['message']
        ];
    }
    
    /**
     * Lấy tất cả bậc phí ship
     */
    public function getAllTiers() {
        if ($this->db) {
            try {
                $stmt = $this->db->query("
                    SELECT * FROM shipping_configs 
                    WHERE is_active = 1 
                    ORDER BY min_order ASC
                ");
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Fallback to default
            }
        }
        
        return $this->defaultConfig;
    }
}

/**
 * Helper function - tính phí ship nhanh
 */
function calculateShipping($subtotal, $db = null) {
    $calculator = new ShippingCalculator($db);
    return $calculator->calculate($subtotal);
}

/**
 * Helper function - tính tổng đơn hàng
 */
function calculateOrderTotal($subtotal, $discount = 0, $customShippingFee = null, $db = null) {
    $calculator = new ShippingCalculator($db);
    return $calculator->calculateTotal($subtotal, $discount, $customShippingFee);
}