<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatAIController;

Route::post('/chat-process', [ChatAIController::class, 'process']);
