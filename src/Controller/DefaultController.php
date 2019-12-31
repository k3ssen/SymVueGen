<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private ?string $testValue = null;

    /**
     * @Route("/", name="default")
     */
    public function index()
    {
        return $this->render('default/index.vue.twig', [
            'testval' => $this->testValue,
        ]);
    }

    /**
     * @Route("/test", name="test")
     */
    public function test()
    {
        return $this->render('default/test.vue.twig');
    }
}
