<?php

namespace Athos\Foundation;

class AthosException extends \Exception {
    private bool $blockUI;

    public function __construct($message, $code = 0, bool $blockUI = false, Exception $previous = null) {
        $this->blockUI = $blockUI;

        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function shouldBlockUI(): bool {
        return $this->blockUI;
    }
}

?>