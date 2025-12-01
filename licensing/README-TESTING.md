# WritgoAI License Testing Guide

## 🎯 Quick Start

Voor het testen van WritgoAI zonder Stripe integratie zijn er drie eenvoudige opties om een test license aan te maken.

---

## Optie 1: Via WordPress Admin (Makkelijkst) ✅

Dit is de snelste manier om direct aan de slag te gaan!

### Stappen:

1. **Activeer Debug Mode** in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   ```

2. **Ga naar de License Manager**:
   - WordPress Admin → WritgoAI → License Manager

3. **Klik op "Generate Test License"**:
   - Je ziet een knop rechtsboven bij "All Licenses"
   - Klik op deze knop om het modal venster te openen

4. **Kies je optie**:
   - **Optie A - Demo License**: Vink "Use fixed demo license" aan voor de vaste `TEST-DEMO-1234-5678` license
   - **Optie B - Custom License**: Laat het vakje leeg en vul je email in + kies een plan

5. **Genereer**: De test license wordt automatisch aangemaakt en geactiveerd!

### Demo License
De vaste demo license is handig voor documentatie en gestandaardiseerde tests:
- **License Key**: `TEST-DEMO-1234-5678`
- **Email**: `demo@writgoai.com`
- **Plan**: Pro (3,000 credits)
- **Status**: Active
- **Geldig**: 1 jaar

---

## Optie 2: Via PHP CLI Script 💻

Voor developers die scripts willen automatiseren of meerdere licenses willen aanmaken.

### Vereisten:
- PHP CLI toegang
- Database credentials in `licensing/config.php`
- Database tabellen aangemaakt (run migrations)

### Gebruik:

**Demo License aanmaken:**
```bash
cd /path/to/plugin/licensing/scripts
php generate-test-license.php --demo
```

**Custom License aanmaken:**
```bash
php generate-test-license.php --email=jouw@email.com --plan=pro
```

**Met custom credits:**
```bash
php generate-test-license.php --email=jouw@email.com --plan=pro --credits=5000
```

### Beschikbare Parameters:
- `--demo` - Genereer vaste demo license (TEST-DEMO-1234-5678)
- `--email=EMAIL` - Email adres (verplicht als niet --demo)
- `--plan=PLAN` - Plan type: `starter`, `pro`, of `enterprise` (default: `pro`)
- `--credits=NUMBER` - Aantal credits (optioneel, default op basis van plan)

### Voorbeeld Output:
```
╔════════════════════════════════════════════════════════════╗
║           ✅ TEST LICENSE SUCCESVOL AANGEMAAKT             ║
╠════════════════════════════════════════════════════════════╣
║  License Key: TEST-A1B2-C3D4-E5F6                          ║
║  Email:       jouw@email.com                               ║
║  Plan:        Pro                                          ║
║  Credits:     3000                                         ║
║  Status:      Active                                       ║
║  Geldig tot:  2025-12-01                                   ║
╚════════════════════════════════════════════════════════════╝

📋 Kopieer deze license key naar je WordPress plugin instellingen.
```

---

## Optie 3: Via SQL Script 🗄️

Voor directe database manipulatie of gebruik in CI/CD pipelines.

### Vereisten:
- MySQL/MariaDB toegang
- Database tabellen aangemaakt

### Gebruik:

1. **Open het SQL bestand**: `licensing/db/seed-test-license.sql`

2. **Pas de variabelen aan** (bovenaan het bestand):
   ```sql
   SET @email = 'YOUR_EMAIL@EXAMPLE.COM';  -- Vervang met je email
   SET @plan = 'pro';                      -- starter, pro, of enterprise
   SET @credits = 3000;                    -- Credits voor het plan
   ```

3. **Run het script**:
   ```bash
   mysql -u username -p database_name < licensing/db/seed-test-license.sql
   ```

4. **Kopieer de gegenereerde license key** uit de output naar WordPress

---

## 📊 Test License Limieten

| Plan | Maandelijkse Prijs | Credits | Geldigheid |
|------|-------------------|---------|------------|
| **Starter** | €29 | 1,000 | 1 jaar |
| **Pro** | €79 | 3,000 | 1 jaar |
| **Enterprise** | €199 | 10,000 | 1 jaar |

### Credit Costs per Actie:
| Actie | Credits |
|-------|---------|
| AI Rewrite (small) | 10 |
| AI Rewrite (paragraph) | 25 |
| AI Rewrite (full) | 50 |
| AI Image | 100 |
| SEO Analysis | 20 |
| Internal Links | 5 |
| Keyword Research | 15 |

---

## ⚙️ WordPress Plugin Activatie

Na het aanmaken van een test license via **Optie 2 of 3**:

1. Ga naar **WordPress Admin → WritgoAI → Instellingen**
2. Plak de license key in het veld
3. Vul je email in
4. Klik op "Activate License"

Bij **Optie 1** wordt de license automatisch geactiveerd! ✨

---

## 🔍 Test License Herkennen

Alle test licenses zijn herkenbaar aan:
- ✅ License key begint met `TEST-`
- ✅ Status badge toont "(Test)" of "(Demo)" achter de plan naam
- ✅ Stripe IDs beginnen met `cus_test_` en `sub_test_`
- ✅ In de metadata staat `is_test: true`

De vaste demo license:
- ✅ License key is altijd `TEST-DEMO-1234-5678`
- ✅ Email is `demo@writgoai.com`
- ✅ Metadata bevat `is_demo: true`

---

## ⚠️ Belangrijke Waarschuwingen

### Security
- 🔒 Test licenses werken **alleen** wanneer `WP_DEBUG` is ingeschakeld
- 🔒 Gebruik **NOOIT** test licenses in productie
- 🔒 Test licenses zijn **niet** verbonden met Stripe
- 🔒 Credits worden lokaal getrackt (niet op license server)

### Limieten
- ⏱️ Test licenses zijn 1 jaar geldig
- 🔄 Credits vernieuwen **niet** automatisch (geen actief abonnement)
- 📊 Gebruik tracking werkt normaal
- 🚫 Plugin updates kunnen beperkt zijn voor test licenses

### Opruimen
Om test licenses te verwijderen:
1. Ga naar **WritgoAI → License Manager**
2. Klik op "View" bij de test license
3. Gebruik de "Delete" functie (indien beschikbaar)

Of via database:
```sql
DELETE FROM licenses WHERE license_key LIKE 'TEST-%';
DELETE FROM user_credits WHERE license_id IN (
    SELECT id FROM licenses WHERE license_key LIKE 'TEST-%'
);
```

---

## 🐛 Troubleshooting

### "Generate Test License" knop verschijnt niet
- ✅ Check of `WP_DEBUG` is ingeschakeld in `wp-config.php`
- ✅ Hard refresh (Ctrl+F5) om cached JavaScript te verversen

### PHP CLI Script Error: "Database error"
- ✅ Controleer `licensing/config.php` voor correcte database credentials
- ✅ Zorg dat database tabellen zijn aangemaakt (run migrations eerst)
- ✅ Check of PHP PDO MySQL extensie is geïnstalleerd

### License Key bestaat al (demo license)
```bash
# Verwijder de bestaande demo license eerst
mysql -u user -p database -e "DELETE FROM licenses WHERE license_key = 'TEST-DEMO-1234-5678'"
```

### License verschijnt niet in WordPress Admin
- ✅ Test licenses worden opgeslagen in `wp_options` tabel
- ✅ Check `writgoai_licenses` option
- ✅ Clear WordPress cache

---

## 📚 Aanvullende Documentatie

- **Setup Guide**: `docs/SETUP.md`
- **API Documentation**: `docs/API.md`
- **Database Schema**: `licensing/db/migrations/`

---

## 💡 Tips

1. **Gebruik de demo license** voor gestandaardiseerde documentatie en screenshots
2. **Genereer custom licenses** voor verschillende test scenario's
3. **Test verschillende plans** om credit limits te valideren
4. **Monitor API usage** in de dashboard tijdens testing

---

## 🤝 Support

Voor vragen of problemen:
- 📧 Email: support@writgoai.com
- 🐛 GitHub Issues: [github.com/Mikeyy1405/WritgoAI-plugin/issues](https://github.com/Mikeyy1405/WritgoAI-plugin/issues)
- 📖 Documentation: [docs.writgoai.com](https://docs.writgoai.com)

---

Made with ❤️ for easy testing and development!
