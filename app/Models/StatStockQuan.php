<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StatStockQuan extends Model
{
    protected $table = 'pmt_stock_quantity';
    protected $primaryKey = 'SNO';
    protected $fillable = 
        [
         'MAT',
         'NEW_INWARD_QUAN',
         'ISSUED_QUAN',
         'BAL_QUAN',
         'TYPE',
        ];

}