// database/migrations/xxxx_xx_xx_create_rentals_table.php

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
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->foreignId('car_id')
                  ->constrained()
                  ->onDelete('cascade');

            $table->date('rental_date');

            $table->date('return_date');

            $table->integer('total_price');

            $table->string('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};