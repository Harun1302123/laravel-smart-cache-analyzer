# Setup Guide - Laravel Smart Cache Analyzer

## ✅ Step 1: Install Dependencies (COMPLETED)

Dependencies are being installed via Composer...

---

## 🧪 Step 2: Test Locally in a Laravel App

### Option A: Local Development (Recommended for testing)

1. **Create or use an existing Laravel application:**
   ```bash
   cd c:\laragon\www
   laravel new test-app
   cd test-app
   ```

2. **Add your package as a local repository in the Laravel app's `composer.json`:**
   ```json
   {
       "repositories": [
           {
               "type": "path",
               "url": "../laravel-package-start"
           }
       ]
   }
   ```

3. **Require the package:**
   ```bash
   composer require harun1302123/laravel-smart-cache-analyzer @dev
   ```

4. **Publish config and migrations:**
   ```bash
   php artisan vendor:publish --provider="SmartCache\Analyzer\SmartCacheServiceProvider"
   php artisan migrate
   ```

5. **Test the dashboard:**
   ```bash
   php artisan serve
   ```
   Visit: `http://localhost:8000/smart-cache`

6. **Test CLI commands:**
   ```bash
   php artisan cache:analyze
   php artisan cache:cleanup --dry-run
   php artisan cache:warm
   ```

### Option B: Install from GitHub (After publishing)

```bash
composer require harun1302123/laravel-smart-cache-analyzer
```

---

## 🧪 Step 3: Run Package Tests

```bash
cd c:\laragon\www\laravel-package-start
composer test
```

Or run specific tests:
```bash
vendor/bin/phpunit tests/Unit/CacheAnalyzerTest.php
```

---

## 📦 Step 4: Publish to GitHub

1. **Create a new repository on GitHub:**
   - Go to: https://github.com/new
   - Repository name: `laravel-smart-cache-analyzer`
   - Description: "Intelligent cache analysis and optimization for Laravel applications"
   - Public/Private: Choose Public for open source
   - Don't initialize with README (we already have one)

2. **Initialize Git and push to GitHub:**
   ```bash
   cd c:\laragon\www\laravel-package-start
   git init
   git add .
   git commit -m "Initial release: Laravel Smart Cache Analyzer v1.0.0"
   git branch -M main
   git remote add origin https://github.com/Harun1302123/laravel-smart-cache-analyzer.git
   git push -u origin main
   ```

3. **Create a release tag:**
   ```bash
   git tag -a v1.0.0 -m "Version 1.0.0 - Initial Release"
   git push origin v1.0.0
   ```

---

## 🚀 Step 5: Publish to Packagist

1. **Go to Packagist.org:**
   - Visit: https://packagist.org/
   - Click "Sign In" (use GitHub to authenticate)

2. **Submit your package:**
   - Click "Submit" in the top menu
   - Enter your GitHub repository URL:
     ```
     https://github.com/Harun1302123/laravel-smart-cache-analyzer
     ```
   - Click "Check"
   - Review and click "Submit"

3. **Set up auto-update (Optional but recommended):**
   - Go to your package page on Packagist
   - Click "Settings"
   - Enable GitHub hook for automatic updates
   - Or use GitHub Actions (see below)

---

## 🔄 GitHub Actions for Auto-Publishing (Optional)

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        laravel: [10.*, 11.*]

    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-progress
        
      - name: Run tests
        run: composer test
```

---

## 📝 Post-Publication Checklist

- [ ] Add GitHub repository description and topics (laravel, cache, performance, php)
- [ ] Add a GitHub repository banner/logo
- [ ] Enable GitHub Discussions for community support
- [ ] Add issue templates
- [ ] Set up GitHub Actions for tests
- [ ] Add badges to README (build status, packagist downloads, version)
- [ ] Share on Laravel News, Reddit (r/laravel), Twitter
- [ ] Create a blog post or video tutorial

---

## 📊 Monitoring Your Package

After publishing, monitor:
- Packagist downloads: https://packagist.org/packages/harun1302123/laravel-smart-cache-analyzer
- GitHub stars and issues
- User feedback and feature requests

---

## 🎉 Success Criteria

Your package is successfully published when:
- ✅ It appears on Packagist
- ✅ You can install it via `composer require`
- ✅ All tests pass
- ✅ Dashboard is accessible
- ✅ CLI commands work

---

## 🆘 Troubleshooting

**Issue: Composer can't find the package**
- Ensure it's published on Packagist
- Check package name matches composer.json
- Try `composer clear-cache`

**Issue: Package not loading in Laravel**
- Check service provider is registered
- Run `composer dump-autoload`
- Clear Laravel cache: `php artisan config:clear`

**Issue: Migrations failing**
- Check database connection
- Ensure migration table names are unique
- Run `php artisan migrate:fresh` in test environment

---

## 📚 Resources

- Laravel Package Development: https://laravel.com/docs/packages
- Packagist Documentation: https://packagist.org/about
- Composer Documentation: https://getcomposer.org/doc/
- Laravel Package Boilerplate: https://laravelpackageboilerplate.com/

---

**Ready to publish? Follow the steps above and your package will be live! 🚀**
