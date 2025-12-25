<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
    class CreateWhatsappMessagesTable extends Migration
{
    protected $connection = 'tenant';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();

            // ربط بالحجز (اختياري)
            $table->foreignId('booking_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // ✅ ربط بالحلاق (اختياري)
            $table->foreignId('barber_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');

            // بيانات الرسالة
            $table->string('phone_number', 20);
            $table->enum('message_type', ['incoming', 'outgoing']);
            $table->text('message_content');

            // WhatsApp API
            $table->string('whatsapp_message_id')->nullable();

            // ✅ تخزين رد API
            $table->json('response_json')->nullable();

            // حالة الرسالة
            $table->string('status')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}
