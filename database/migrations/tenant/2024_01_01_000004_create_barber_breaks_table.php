<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
        class CreateBarberBreaksTable extends Migration
{
    protected $connection = 'tenant';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('barber_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barber_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('day_of_week')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_recurring')->default(false);
               // 👇 هذا هو الناقص
            $table->unsignedBigInteger('updated_by_admin')->nullable();
            $table->timestamps();
            
            $table->index(['barber_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barber_breaks');
    }
}
