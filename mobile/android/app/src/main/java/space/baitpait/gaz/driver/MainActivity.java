package space.baitpait.gaz.driver;

import android.os.Bundle;
import android.webkit.CookieManager;
import android.webkit.WebView;
import com.getcapacitor.BridgeActivity;

/**
 * Business Purpose: تفعيل كوكيز WebView حتى يعمل تسجيل دخول Laravel (CSRF/Session)
 * داخل تطبيق السائق بدون خطأ 419 Page Expired.
 */
public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);

        CookieManager cookieManager = CookieManager.getInstance();
        cookieManager.setAcceptCookie(true);

        WebView webView = this.bridge != null ? this.bridge.getWebView() : null;
        if (webView != null) {
            cookieManager.setAcceptThirdPartyCookies(webView, true);
        }
    }
}
