<!-- Hero Section -->
        <div class="bg-gradient-to-br from-luxury-primary via-purple-900 to-luxury-primary text-white py-16 md:py-24">
            <div class="container mx-auto px-4 md:px-6">
                <h1 class="text-4xl md:text-5xl font-luxury font-bold mb-4"><?= e(t('contact_us')) ?></h1>
                <p class="text-lg text-purple-100">ئێمە حەز دەکەین گوێمان لێت بێت. ئەمڕۆ لەگەڵمان لە پەیوەندیدابە.</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container mx-auto px-4 md:px-6 py-12 md:py-16">
            <?php
            $flash = getFlashMessage();
            if ($flash):
                echo alert($flash['message'], $flash['type']);
            endif;
            ?>

            <div class="grid md:grid-cols-3 gap-12 mb-16">
                <!-- Contact Form -->
                <div class="md:col-span-2">
                    <h2 class="text-2xl font-bold text-luxury-primary mb-6">نامەیەکمان بۆ بنێرە</h2>
                    <form method="POST" class="bg-white rounded-lg border border-gray-200 p-8">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="name">
                                ناوی تەواو *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="text" 
                                name="name" 
                                id="name" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="email">
                                ئیمەیڵ *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="email" 
                                name="email" 
                                id="email" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="subject">
                                بابەت *
                            </label>
                            <input 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                type="text" 
                                name="subject" 
                                id="subject" 
                                required
                            >
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-2" for="message">
                                نامە *
                            </label>
                            <textarea 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-luxury-primary" 
                                name="message" 
                                id="message" 
                                rows="6" 
                                required
                            ></textarea>
                        </div>

                        <button 
                            type="submit" 
                            name="submit" 
                            class="w-full bg-luxury-primary hover:bg-purple-800 text-white font-bold py-3 px-4 rounded-lg transition-colors"
                        >
                            ناردنی نامە
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div>
                    <h2 class="text-2xl font-bold text-luxury-primary mb-8">زانیاری پەیوەندیکردن</h2>
                    
                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-phone text-luxury-accent ms-3"></i> تەلەفۆن
                        </h3>
                        <p class="text-gray-600" dir="ltr">+964 750 123 4567</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-envelope text-luxury-accent ms-3"></i> ئیمەیڵ
                        </h3>
                        <p class="text-gray-600"><a href="mailto:info@bloomandvine.com" class="hover:text-luxury-accent">info@bloomandvine.com</a></p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-map-marker-alt text-luxury-accent ms-3"></i> ناونیشان
                        </h3>
                        <p class="text-gray-600">هەرێمی کوردستان، عێراق</p>
                    </div>

                    <div class="mb-8">
                        <h3 class="font-semibold text-gray-900 mb-4">کاتەکانی کارکردن</h3>
                        <ul class="text-gray-600 space-y-2">
                            <li>دووشەممە - هەینی: ٩ی بەیانی - ٦ی ئێوارە</li>
                            <li>شەممە: ١٠ی بەیانی - ٥ی ئێوارە</li>
                            <li>یەکشەممە: داخراوە</li>
                        </ul>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3">لەگەڵمان بە</h3>
                        <div class="flex gap-3">
                            <a href="https://facebook.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://instagram.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://twitter.com" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me" class="w-10 h-10 bg-luxury-primary text-white rounded-full flex items-center justify-center hover:bg-purple-800 transition-colors">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>