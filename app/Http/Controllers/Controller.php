<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Subacquirer Payment Integration API",
    description: "API for integrating with payment subacquirers (PIX and Withdraw transactions)",
    contact: new OA\Contact(
        email: "support@example.com"
    ),
    license: new OA\License(
        name: "MIT"
    )
)]
#[OA\Server(
    url: "http://localhost:8000/api",
    description: "Local development server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    name: "Authorization",
    in: "header",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
abstract class Controller
{
    //
}
