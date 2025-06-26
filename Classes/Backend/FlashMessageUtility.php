<?php
declare(strict_types=1);
namespace Foo\Bar\Utility;

use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendFlashMessageUtility
{
    protected static ?self $instance = null;
    protected FlashMessageService $flashMessageService;


    public function __construct()
    {
        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
    }

    public static function get() : self
    {
        if (self::$instance === null)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function flashMessage(string $title, string $message, ContextualFeedbackSeverity $severity) : void
    {
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE);
        $flashMessageQueue?->enqueue($flashMessage);
    }

    public function flush() : void
    {
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'xmlhttprequest')
        {
            // ByPass AJAX Response
            return;
        }

        $flashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier(FlashMessageQueue::NOTIFICATION_QUEUE);

        $jsonMessageQueue = [];
        foreach($flashMessageQueue?->getAllMessages() as $flashMessage)
        {
            $jsonMessageQueue[] = [
                'title'    => $flashMessage->getTitle(),
                'message'  => $flashMessage->getMessage(),
                'severity' => $flashMessage->getSeverity(),
            ];
        }

        if (count($jsonMessageQueue) > 0)
        {
            $flashMessageQueue?->getAllMessagesAndFlush();

            $response = new JsonResponse([
                'hasErrors' => true,
                'messages' => $jsonMessageQueue
            ]);

            throw new ImmediateResponseException($response);
        }
    }
}
