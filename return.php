<?php

/**
 * Copyright (c) 2013, EBANX Tecnologia da Informação Ltda.
 *  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice, this
 * list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * Neither the name of EBANX nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */


/**
 * Bind the EBANX callbacks to the init hook
 */
add_action('init', 'ebanx_router');
function ebanx_router()
{
  $request_url = $_SERVER['REQUEST_URI'];

  if (preg_match('/ebanx\/return\/.*/', $request_url))
  {
    ebanx_return_response();
    exit;
  }
  else if (preg_match('/ebanx\/notify\/.*/', $request_url))
  {
    ebanx_notify_response();
    exit;
  }
}

/**
 * EBANX notification action - gets called when a payment is updated
 * @return void
 */
function ebanx_notify_response()
{
  $ebanxWC = new WC_Gateway_Ebanx();

  $hashes = isset($_REQUEST['hash_codes']) ? $_REQUEST['hash_codes'] : null;

  if (!isset($hashes))
  {
    echo 'NOK: no hashes were sent.';
    return;
  }

  $hashes = explode(',', $hashes);

  foreach ($hashes as $hash)
  {
    try
    {
      $response = \Ebanx\Ebanx::doQuery(array('hash' => $hash));

      if (isset($response->status) && $response->status == 'SUCCESS')
      {
        $orderId = (int) $response->payment->order_number;
        $order = new WC_Order($orderId);

        if ($order)
        {
          if ($response->payment->status == 'CA')
          {
            $order->update_status('cancelled', 'Payment cancelled via IPN.');
            echo "OK: Payment {$hash} was cancelled via IPN";
          }
          elseif ($response->payment->status == 'CO')
          {
            $order->add_order_note(__('EBANX payment completed, Hash: '.$response->payment->hash, 'woocommerce'));
            $order->payment_complete($response->payment->order_number);
            echo "OK: Payment {$hash} was completed";
          }
          elseif ($response->payment->status == 'OP' || $response->payment->status == 'PE')
          {
            echo "SKIP: Payment {$hash} is pending.";
          }
        }
        else
        {
          echo "NOK: payment {$hash} was not found.";
        }
      }
    }
    catch (Exception $e)
    {
      echo "NOK: payment {$hash} threw an exception => " . $e->getMessage();
    }
  }

  exit;
}

/**
 * EBANX return response - gets called after returning from the EBANX checkout
 * @return void
 */
function ebanx_return_response()
{
  $ebanxWC = new WC_Gateway_Ebanx();

  $response = \Ebanx\Ebanx::doQuery(array('hash' => $_GET['hash']));

  $orderId = (int) $response->payment->order_number;
  $order   = new WC_Order($orderId);

  if (isset($response->status) && $response->status == 'SUCCESS' && ($response->payment->status == 'PE' || $response->payment->status == 'CO'))
  {
    if ($response->payment->status == 'CO')
    {
      $order->add_order_note(__('EBANX payment completed, Hash: '.$response->payment->hash, 'woocommerce'));
      $order->payment_complete();
    }
    else if($response->payment->status == 'PE')
    {
      $order->update_status('pending', 'Payment pending via Response URL.');
    }

    wp_redirect($ebanxWC->get_return_url($order));
  }
  else
  {
    $order->update_status('cancelled', 'Payment cancelled via Response URL.');
    wp_redirect($order->get_cancel_order_url());
  }

  exit;
}
