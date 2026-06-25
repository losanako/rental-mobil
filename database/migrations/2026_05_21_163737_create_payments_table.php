// database/migrations/xxxx_xx_xx_create_payments_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rental_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->date('payment_date');

            $table->integer('amount');

            $table->string('payment_method');

            $table->string('payment_status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};