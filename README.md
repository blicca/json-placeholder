# json-placeholder

<h2>Kurulum:</h2>

Plugin dosyalarını WordPress sitenizin wp-content/plugins/ dizinine yükleyin.
WordPress yönetici panelinden "Eklentiler" bölümüne gidin ve "Özel JSONPlaceholder Eklentisi"ni etkinleştirin.

<h2>Endpoint Aktivasyonu:</h2>

WordPress yönetici panelinden "Ayarlar" -> "Kalıcı Bağlantılar" bölümüne gidin.
Kalıcı bağlantılar yapılandırmasını güncellemek için "Değişiklikleri Kaydet" düğmesine tıklayın. Bu, özel endpoint'inizi WordPress'e tanıtacaktır.

<h2>Kullanım:</h2>

Eklenti, WordPress sitenizin başlangıç ​​sayfasına bir özel endpoint ekler. Endpoint'e erişmek için tarayıcınızda siteadi.com/custom-endpoint/ URL'sini ziyaret edin. Bu, JSONPlaceholder API'sinden kullanıcıları çekecek ve bir tablo halinde görüntüleyecektir.
Kullanıcı adlarına tıkladığınızda, ilgili kullanıcının ayrıntılarını AJAX aracılığıyla yükleme işlemi gerçekleşir.
