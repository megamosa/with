<?php
namespace MagoArab\WithoutEmail\Controller\Otp;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Session\SessionManagerInterface;
use MagoArab\WithoutEmail\Helper\Config;
use MagoArab\WithoutEmail\Helper\WhatsappService;
use MagoArab\WithoutEmail\Model\OtpRateLimit;
use Psr\Log\LoggerInterface;

class Send implements HttpPostActionInterface
{
    protected $request;
    protected $resultJsonFactory;
    protected $session;
    protected $configHelper;
    protected $whatsappService;
    protected $otpRateLimit;
    protected $logger;

    public function __construct(
        RequestInterface $request,
        JsonFactory $resultJsonFactory,
        SessionManagerInterface $session,
        Config $configHelper,
        WhatsappService $whatsappService,
        OtpRateLimit $otpRateLimit,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->session = $session;
        $this->configHelper = $configHelper;
        $this->whatsappService = $whatsappService;
        $this->otpRateLimit = $otpRateLimit;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            // Log request for debugging
            $this->logger->info('OTP Send Request', [
                'phone_number' => $this->request->getParam('phone_number'),
                'type' => $this->request->getParam('type')
            ]);
            
            if (!$this->configHelper->isEnabled() || !$this->configHelper->isOtpEnabled()) {
                throw new \Exception('OTP service is not enabled.');
            }
            
            $phoneNumber = $this->request->getParam('phone_number');
            if (empty($phoneNumber)) {
                throw new \Exception('Phone number is required.');
            }
            
            // Check rate limit
            $canSend = $this->otpRateLimit->canSendOtp($phoneNumber);
            if (!$canSend['allowed']) {
                throw new \Exception($canSend['message']);
            }
            
            // Generate OTP
            $otp = $this->whatsappService->generateOtp();
            
            // Store OTP in session
            $otpData = [
                'code' => $otp,
                'expiry' => date('Y-m-d H:i:s', strtotime('+' . $this->configHelper->getOtpExpiry() . ' minutes')),
                'verified' => false,
                'attempts' => 0
            ];
            
            $this->session->setData('otp_' . $phoneNumber, $otpData);
            
            // Send OTP via WhatsApp
            $sent = $this->whatsappService->sendOtp($phoneNumber, $otp);
            
            // Log attempt
            $this->otpRateLimit->logAttempt($phoneNumber, $sent);
            
            if ($sent) {
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('OTP sent successfully to your WhatsApp.')
                ]);
            } else {
                throw new \Exception('Failed to send OTP. Please try again.');
            }
            
        } catch (\Exception $e) {
            $this->logger->error('OTP Send Error: ' . $e->getMessage());
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}