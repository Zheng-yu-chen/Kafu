<?php include('header.php'); ?>

<style>
    .privacy-container {
        max-width: 800px;
        margin: 40px auto;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        line-height: 1.8;
        color: #333;
    }
    .privacy-header {
        text-align: center;
        margin-bottom: 30px;
    }
    .privacy-container h2 { color: var(--fujen-blue, #002B5B); }
    .privacy-container h3 { margin-top: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
    .back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #FF8C42; color: white; text-decoration: none; border-radius: 5px; }
</style>

<div class="privacy-container">
    <h2 class="privacy-header">
        KaFu<br>隱私權條款與熱量計算參考說明
    </h2>
    
    <h3>一、隱私權保護政策</h3>
    <p>我們極度重視您的隱私。本系統僅收集您註冊時所提供的必要資訊（姓名、帳號）。這些資料僅用於系統登入、個人化設定及飲食紀錄管理。我們不會將您的個人資料洩漏給任何第三方單位。</p>

    <h3>二、免責聲明（熱量數值僅供參考）</h3>
    <p>本系統所提供的各項飲食熱量數值，係根據公開資料進行推算與估計。由於以下原因，實際數值可能存在誤差：</p>
    <ul>
        <li>食物的烹飪方式、調味料使用及食材比例差異。</li>
        <li>餐點製作過程中的份量誤差。</li>
        <li>公開資料庫與實際產品間的微小落差。</li>
    </ul>
    <p>因此，本系統之熱量數據<strong>僅供參考</strong>，不具備醫療診斷或專業營養評估之效力。若您有嚴格的飲食控制需求，建議諮詢專業營養師或醫療機構。</p>

    <h3>三、同意條款</h3>
    <p>當您在註冊頁面勾選同意條款並完成註冊，即代表您已閱讀並理解上述說明，並同意本系統之使用規則。</p>

    <a href="javascript:window.close();" class="back-btn">我已閱讀，關閉並返回註冊</a>
</div>

<?php include('footer.php'); ?>