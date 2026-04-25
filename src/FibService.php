<?php
declare(strict_types=1);

/**
 * FIB Payment Service
 * Wraps the Node.js fibpay SDK bridge
 */
class FibService {
    /**
     * Run the Node.js bridge command
     * 
     * @param array $args CLI arguments
     * @return array Response data
     * @throws Exception on error
     */
    private static function runBridge(array $args): array {
        // Find node executable - try to find it in common paths if not in PATH
        $node = 'node';
        
        $bridgePath = realpath(__DIR__ . '/../fib-payment/fib-bridge.js');
        if (!$bridgePath) {
            throw new Exception("FIB bridge script not found at " . __DIR__ . '/../fib-payment/fib-bridge.js');
        }

        $command = $node . ' ' . escapeshellarg($bridgePath) . ' ' . implode(' ', array_map('escapeshellarg', $args));
        
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);
        
        $rawOutput = implode("\n", $output);
        
        // Extract JSON part using markers
        $json = $rawOutput;
        if (preg_match('/---FIB-JSON-START---(.*?)---FIB-JSON-END---/s', $rawOutput, $matches)) {
            $json = $matches[1];
        }

        $data = json_decode($json, true);
        
        if ($returnVar !== 0 || $data === null) {
            $errorMsg = isset($data['error']) ? $data['error'] : 'Unknown bridge error';
            if (isset($data['body'])) {
                $errorMsg .= ' - ' . json_encode($data['body']);
            }
            if ($data === null) {
                $errorMsg = "Invalid JSON from bridge: " . $rawOutput;
            }
            throw new Exception("FIB Error: " . $errorMsg);
        }
        
        return $data;
    }

    /**
     * Create a new payment
     */
    public static function createPayment(int $amount, string $description, ?string $callbackUrl = null, ?string $redirectUrl = null): array {
        return self::runBridge([
            'create',
            (string)$amount,
            'IQD',
            $description,
            $callbackUrl ?? 'null',
            $redirectUrl ?? 'null'
        ]);
    }

    /**
     * Get payment status
     */
    public static function getPaymentStatus(string $paymentId): array {
        return self::runBridge(['status', $paymentId]);
    }
}
