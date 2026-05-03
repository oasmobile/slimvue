<?php

namespace Oasis\SlimVue\Demo;

use Oasis\Mlib\Http\MicroKernel;
use Oasis\SlimVue\TwigBridgeInfo;
use Symfony\Component\HttpFoundation\Response;

class DemoController
{
    /**
     * 主页：演示多种 bridge 数据类型 + getExecTwig
     */
    public function homeAction(MicroKernel $kernel): Response
    {
        $twig = $kernel->getTwig();

        $bridge = new TwigBridgeInfo([
            'user'    => 'demo',
            'count'   => 42,
            'ratio'   => 3.14,
            'active'  => true,
            'tags'    => ['vue', 'php'],
            'profile' => ['name' => 'SlimVue', 'meta' => ['v' => 4]],
        ]);

        // 演示 getExecTwig：将 pages 路径转换为 controllers 路径
        $controllerTwig = $bridge->getExecTwig('slimvue/pages/index.twig');

        return new Response(
            $twig->render($controllerTwig, [
                'title'  => 'SlimVue Demo',
                'bridge' => $bridge,
            ]),
        );
    }

    /**
     * 子页面：演示第二个路由入口
     */
    public function subpageAction(MicroKernel $kernel): Response
    {
        $twig = $kernel->getTwig();

        $bridge = new TwigBridgeInfo([
            'page' => 'subpage',
        ]);

        return new Response(
            $twig->render('slimvue/pages/subpage.twig', [
                'title'  => 'SlimVue Subpage',
                'bridge' => $bridge,
            ]),
        );
    }

    /**
     * 应用层错误处理演示（Gatekeep Q4）
     */
    public function errorDemoAction(MicroKernel $kernel): Response
    {
        $twig = $kernel->getTwig();

        try {
            // 模拟业务逻辑异常
            throw new \RuntimeException('This is a demo application-level error');
        } catch (\RuntimeException $e) {
            return new Response(
                $twig->render('error.twig', [
                    'title'   => 'Application Error',
                    'message' => $e->getMessage(),
                ]),
                status: 500,
            );
        }
    }
}
