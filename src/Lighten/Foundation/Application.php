<?php

namespace Lighten\Foundation;


class Application
{
    public Router $router;
    public Request $request;
    public Response $response;
    public static Application $app;
    public static string $basePath;
    public Controller $controller;

    public View $view;

    public function __construct(string $basePath, array $appConfig)
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->controller = new Controller();
        self::$app = $this;
        self::$basePath = $basePath;
        $this->router = new Router($this->request, $this->response);
        $this->view = new View();
    }

    public function run()
    {
        try {
            echo $this->router->resolve();
        } catch (\Exception $exception) {
            echo $this->view->render('errors', ['exception' => $exception]);
        }
    }
}