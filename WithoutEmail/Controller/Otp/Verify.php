<?php
namespace MagoArab\WithoutEmail\Controller\Otp;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Model\OtpRateLimit;
use Psr\Log\LoggerInterface;

class Verify implements HttpPostActionInterface
{
    protected $request;
    protected $resultJsonFactory;
    protected $session;
    protected $configHelper;
    protected $otpRateLimit;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        SessionManagerInterface $session,
        Config $configHelper,
        OtpRateLimit $otpRateLimit,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->configHelper = $configHelper;
        $this->otpRateLimit = $otpRateLimit;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            if (!$this->configHelper->isEnabled()) {
                throw new \Exception('This feature is not enabled.');
            }
            
            $phoneNumber = $this->request->getParam('phone_number');
            $otpCode = $this->request->getParam('otp_code');
            
            if (empty($phoneNumber) || empty($otpCode)) {
                throw new \Exception('Phone number and OTP code are required.');
            }
            
            // Get OTP data from session
            $otpData = $this->session->getData('otp_' . $phoneNumber);
            
            if (!$otpData || !isset($otpData['code']) || !isset($otpData['expiry'])) {
                throw new \Exception('OTP has not been sent. Please request a new OTP.');
            }
            
            // Check attempts
            if (isset($otpData['attempts']) && $otpData['attempts'] >= 3) {
                $this->session->unsetData('otp_' . $phoneNumber);
                throw new \Exception('Too many failed attempts. Please request a new OTP.');
            }
            
            // Check if OTP has expired
            $currentTime = new \DateTime();
            $expiry = new \DateTime($otpData['expiry']);
            
            if ($currentTime > $expiry) {
                $this->session->unsetData('otp_' . $phoneNumber);
                throw new \Exception('OTP has expired. Please request a new OTP.');
            }
            
            // Verify OTP
            if ($otpData['code'] !== $otpCode) {
                $otpData['attempts'] = ($otpData['attempts'] ?? 0) + 1;
                $this->session->setData('otp_' . $phoneNumber, $otpData);
                
                $this->otpRateLimit->logAttempt($phoneNumber, false);
                
                throw new \Exception('Invalid OTP code. Please try again.');
            }
            
            // Mark OTP as verified
            $otpData['verified'] = true;
            $this->session->setData('otp_' . $phoneNumber, $otpData);
            
            return $resultJson->setData([
                'success' => true,
                'message' => __('OTP verified successfully.')
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('OTP Verify Error: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}