<?php

namespace App\Enum;

enum ProductCategory: string
{
    case TISANE = 'tisane';
    case COMPLEMENT_ALIMENTAIRE = 'complement alimentaire';
    case SHILAJIT = 'shilajit';
}