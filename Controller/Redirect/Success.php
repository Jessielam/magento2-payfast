<?php
/**
 * Copyright (c) 2008 PayFast (Pty) Ltd
 * You (being anyone who is not PayFast (Pty) Ltd) may download and use this plugin / code in your own website in conjunction with a registered and active PayFast account. If your PayFast account is terminated for any reason, you may not use this plugin / code or part thereof.
 * Except as expressly indicated in this licence, you may not use, copy, modify or distribute this plugin / code or part thereof in any way.
 */
namespace Payfast\Payfast\Controller\Redirect;

//use Magento\Sales\Model\Service\InvoiceService;

/**
 * Responsible for loading page content.
 *
 * This is a basic controller that only loads the corresponding layout file. It may duplicate other such
 * controllers, and thus it is considered tech debt. This code duplication will be resolved in future releases.
 */
class Success extends \Payfast\Payfast\Controller\AbstractPayfast
{
    //generate invoice use params（给用户发送邮件）
    const SUCCESS_PAID_COMMENT = 'generate invoice by payfast';
    const SEND_EMAIL = 1;

    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $resultPageFactory;

    /**
     * execute
     * this method illustrate magento2 super power.
     */

    public function execute()
    {
        $pre = __METHOD__ . " : ";
        $this->_logger->debug($pre . 'bof');

        try
        {
            // NOTE: 支付成功，把订单状态改为paid [start]
            $this->_order = $this->_checkoutSession->getLastRealOrder();
            $this->generateInvoice($this->_order->getId());
            // [end]
            $this->_redirect('checkout/onepage/success');

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->error($pre . $e->getMessage());

            $this->messageManager->addExceptionMessage($e, $e->getMessage());
            $this->_redirect('checkout/cart');
        } catch (\Exception $e) {
            $this->_logger->error($pre . $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('We can\'t start PayFast Checkout.'));
            $this->_redirect('checkout/cart');
        }

        return '';
    }

    private function getInvoiceObject()
    {
        return $this->_objectManager->get(\Magento\Sales\Model\Service\InvoiceService::class);
    }

    private function getInvoiceSender()
    {
        return $this->_objectManager->get(\Magento\Sales\Model\Order\Email\Sender\InvoiceSender::class);
    }

    //为成功付款的用户创建发片，并且修改订单状态
    public function generateInvoice($orderId = null)
    {
        if ($orderId) {
            $data = [
                'comment_text' => self::SUCCESS_PAID_COMMENT,
                'send_email' => self::SEND_EMAIL,
            ];
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setCommentText(self::SUCCESS_PAID_COMMENT);
            try {
                $invoiceItems = [];

                /** @var \Magento\Sales\Model\Order $order */
                $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);
                //$data = $order->getData();

                if (!$order->canInvoice()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The order does not allow an invoice to be created.')
                    );
                }

                $invoice = $this->getInvoiceObject()->prepareInvoice($order, $invoiceItems);

                if (!$invoice) {
                    throw new LocalizedException(__('We can\'t save the invoice right now.'));
                }

                if (!$invoice->getTotalQty()) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('You can\'t create an invoice without products.')
                    );
                }
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);

                if (!empty($data['comment_text'])) {
                    $invoice->addComment(
                        $data['comment_text'],
                        isset($data['comment_customer_notify']),
                        isset($data['is_visible_on_front'])
                    );

                    $invoice->setCustomerNote($data['comment_text']);
                    $invoice->setCustomerNoteNotify(isset($data['comment_customer_notify']));
                }
                //这里会调用生成sales payment transaction
                $invoice->register();

                $invoice->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));
                $invoice->getOrder()->setIsInProcess(true);

                $transactionSave = $this->_objectManager->create(\Magento\Framework\DB\Transaction::class)
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());

                $transactionSave->save();
                //$this->messageManager->addSuccess(__('The invoice has been created.'));
                $order->addStatusHistoryComment(__('Notified customer about invoice #%1.', $invoice->getIncrementId()));
                $order->setIsCustomerNotified(true);
                $order->save();

                // send email
                try {
                    if (!empty($data['send_email'])) {
                        $this->getInvoiceSender()->send($invoice);
                    }
                } catch (\Exception $e) {
                    $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
                    $this->messageManager->addError(__('We can\'t send the invoice email right now.'));
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t save the invoice right now.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }

            return true;
        }
    }
}
