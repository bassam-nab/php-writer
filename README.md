# php-writer
```php

        (new PhpWriter())
            ->openClass('Models\Payment', function (PhpWriter $doc) {
                return $doc
                    ->addUse('SomeTrait')
                    ->addConstant('STATUS_PENDING', 1)
                    ->addConstant('STATUS_REJECTED', 2)
                    ->addConstant('STATUS_ACCEPTED', 3)
                    ->addLine()
                    ->addVar('payment_id')
                    ->addVar('numberOfPayments', 0, 'public', true)
                    ->addLine()
                    ->addMethod('doPay', ['$transaction_id', '...$otherOrders'], 'public', true)
                    ->addMethod('setPrice', ['$price' => 0]);
            }, 'SomeClass', ['myInterface', 'AndOtherInterface'])->exportFile();
   
```
