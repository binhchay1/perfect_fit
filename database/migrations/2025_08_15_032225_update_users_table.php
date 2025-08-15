<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('country')->nullable(); //quốc gia
            $table->string('province')->nullable(); //tỉnh/thành phố
            $table->string('district')->nullable(); //huyện
            $table->string('ward')->nullable(); // phường/xã
            $table->string('address')->nullable(); //địa chỉ
            $table->string('ip_address')->nullable(); //ip_address
            $table->string('role')->nullable();
            $table->string('postal_code')->nullable(); //mã bưu điện
            $table->string('phone')->nullable();
            $table->integer('status')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->string('facebook_id')->nullable();
            $table->string('google_id') ->nullable();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('members');
            $table->dropColumn('province');
            $table->dropColumn('district');
            $table->dropColumn('ward');
            $table->dropColumn('address');
            $table->dropColumn('ip_address');
            $table->dropColumn('role');
            $table->dropColumn('postal_code');
            $table->dropColumn('phone');
            $table->dropColumn('status');
            $table->dropColumn('profile_photo_path', 2048);
            $table->dropColumn('facebook_id')->nullable();
            $table->dropColumn('google_id') ->nullable();
        });
    }
};
