<?php
    use Athos\Foundation\Controller;

    class HomeController extends Controller {
        public function defaultAction(){
            $this->smarty->assign('welcome', 'Welcome to Athos-Foundation!');
        }

        public function requiresCredentials(): bool {
            return false;
        }
    }
?>
