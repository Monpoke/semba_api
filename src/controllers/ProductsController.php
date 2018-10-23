<?php

/**
 * Class ProductsController.
 * @url /
 */
class ProductsController {

    public function __invoke(\Slim\Http\Request $request, \Slim\Http\Response $response, $args) {


        exit(var_dump($app));

        $data = [
            'coucou' => 'tet',
            'fkk' => 5
        ];


        return $response->withJson($data);


    }

}