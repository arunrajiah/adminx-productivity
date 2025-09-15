# AdminX Productivity - Deployment Guide

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Local Development](#local-development)
3. [Testing Procedures](#testing-procedures)
4. [Build Process](#build-process)
5. [Packaging for Distribution](#packaging-for-distribution)
6. [Release Management](#release-management)
7. [WordPress.org Submission](#wordpressorg-submission)
8. [Deployment Checklist](#deployment-checklist)

## Development Environment Setup

### Prerequisites

- **Local WordPress Installation**
  - WordPress 5.0 or higher
  - PHP 7.4 or higher
  - MySQL 5.6 or higher
  - Apache/Nginx web server

- **Development Tools**
  - Git for version control
  - Code editor (VS Code, PHPStorm, etc.)
  - WordPress CLI (WP-CLI)
  - Composer for dependency management
  - Node.js and npm (for build tools)

### Recommended Local Environment

#### Using Local by Flywheel
1. Download and install Local by Flywheel
2. Create a new WordPress site
3. Configure PHP version to 7.4+
4. Enable Xdebug for debugging

#### Using XAMPP/MAMP
1. Install XAMPP or MAMP
2. Download WordPress and extract to htdocs
3. Create database and configure wp-config.php
4. Complete WordPress installation

#### Using Docker
```bash
# Clone WordPress with Docker
git clone https://github.com/docker/awesome-compose.git
cd awesome-compose/wordpress-mysql
docker-compose up -d
```

## Local Development

### Setting Up the Plugin

1. **Clone the Repository**
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   git clone https://github.com/adminx-team/adminx-productivity.git
   ```

2. **Install Dependencies**
   ```bash
   cd adminx-productivity
   composer install --dev
   npm install
   ```

3. **Activate the Plugin**
   - Log in to WordPress admin
   - Navigate to Plugins
   - Activate "AdminX Productivity"

### Development Workflow

#### File Structure
```
adminx-productivity/
├── adminx-productivity.php     # Main plugin file
├── includes/                   # Core PHP classes
│   ├── class-notifications-manager.php
│   ├── class-role-manager.php
│   ├── class-activity-logger.php
│   └── class-dashboard-widgets.php
├── assets/                     # CSS/JS files
│   ├── admin.css
│   └── admin.js
├── templates/                  # Admin page templates
├── docs/                       # Documentation
├── tests/                      # Unit tests
├── .github/workflows/          # CI/CD workflows
├── composer.json               # PHP dependencies
├── package.json               # Node.js dependencies
└── readme.txt                  # WordPress.org readme
```

#### Coding Standards

1. **PHP Standards**
   ```bash
   # Install WordPress Coding Standards
   composer global require wp-coding-standards/wpcs
   
   # Check code standards
   phpcs --standard=WordPress ./
   
   # Fix code standards
   phpcbf --standard=WordPress ./
   ```

2. **JavaScript Standards**
   ```bash
   # Install ESLint
   npm install -g eslint
   
   # Check JavaScript
   eslint assets/admin.js
   ```

#### Database Development

1. **Table Creation**
   - Tables are created automatically on plugin activation
   - Use `dbDelta()` for table creation/updates
   - Always include proper indexes

2. **Database Migrations**
   ```php
   // Check version and run migrations
   $current_version = get_option('adminx_productivity_version');
   if (version_compare($current_version, ADMINX_PRODUCTIVITY_VERSION, '<')) {
       $this->run_migrations();
   }
   ```

## Testing Procedures

### Manual Testing

#### Functionality Testing
1. **Plugin Activation/Deactivation**
   - Activate plugin and verify database tables are created
   - Check admin menu appears
   - Deactivate and verify cleanup

2. **Notifications System**
   - Create test notifications
   - Verify admin bar indicator
   - Test mark as read/dismiss functionality
   - Check automatic system notifications

3. **Role Management**
   - Create custom role
   - Modify role capabilities
   - Test role deletion
   - Verify user access changes

4. **Activity Logging**
   - Perform various admin actions
   - Verify activities are logged
   - Test filtering functionality
   - Check log cleanup

5. **Dashboard Widgets**
   - Verify widgets appear on dashboard
   - Test widget functionality
   - Check responsive design

#### Browser Testing
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

#### WordPress Compatibility
- Test with WordPress 5.0, 5.5, 6.0, 6.3
- Test with popular themes (Twenty Twenty-Three, Astra, etc.)
- Test with common plugins (Yoast SEO, WooCommerce, etc.)

### Automated Testing

#### Unit Tests
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run tests
vendor/bin/phpunit tests/
```

#### Integration Tests
```bash
# WordPress test environment
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

# Run integration tests
vendor/bin/phpunit
```

## Build Process

### Asset Compilation

#### CSS Processing
```bash
# Install build tools
npm install --save-dev sass autoprefixer postcss-cli

# Compile SCSS to CSS
npm run build:css
```

#### JavaScript Processing
```bash
# Install build tools
npm install --save-dev webpack babel-loader

# Build JavaScript
npm run build:js
```

### Build Scripts

#### package.json
```json
{
  "scripts": {
    "build": "npm run build:css && npm run build:js",
    "build:css": "sass assets/scss:assets/css",
    "build:js": "webpack --mode=production",
    "watch": "npm run watch:css & npm run watch:js",
    "watch:css": "sass --watch assets/scss:assets/css",
    "watch:js": "webpack --mode=development --watch"
  }
}
```

## Packaging for Distribution

### Pre-packaging Checklist

- [ ] All features tested and working
- [ ] Code follows WordPress standards
- [ ] Documentation is complete
- [ ] Version numbers updated
- [ ] Changelog updated
- [ ] Security review completed

### Creating Distribution Package

#### Automated Build
```bash
#!/bin/bash
# build.sh

# Set version
VERSION="1.0.0"
PLUGIN_NAME="adminx-productivity"

# Clean previous builds
rm -rf build/
mkdir build/

# Copy plugin files
cp -r . build/$PLUGIN_NAME/

# Remove development files
cd build/$PLUGIN_NAME/
rm -rf .git/
rm -rf node_modules/
rm -rf tests/
rm -rf .github/
rm composer.json package.json

# Create ZIP
cd ..
zip -r $PLUGIN_NAME-$VERSION.zip $PLUGIN_NAME/

echo "Package created: $PLUGIN_NAME-$VERSION.zip"
```

#### Manual Packaging
1. Create clean copy of plugin directory
2. Remove development files:
   - `.git/` directory
   - `node_modules/` directory
   - `tests/` directory
   - `.github/` directory
   - Development configuration files
3. Create ZIP archive

### File Exclusions

Create `.distignore` file:
```
.git/
.github/
node_modules/
tests/
.gitignore
.distignore
composer.json
package.json
webpack.config.js
.eslintrc.js
phpcs.xml
phpunit.xml
```

## Release Management

### Version Control

#### Semantic Versioning
- **Major** (1.0.0): Breaking changes
- **Minor** (1.1.0): New features, backward compatible
- **Patch** (1.0.1): Bug fixes, backward compatible

#### Git Workflow
```bash
# Create release branch
git checkout -b release/1.0.0

# Update version numbers
# Update changelog
# Commit changes
git commit -m "Prepare release 1.0.0"

# Merge to main
git checkout main
git merge release/1.0.0

# Create tag
git tag -a v1.0.0 -m "Release version 1.0.0"

# Push changes
git push origin main --tags
```

### Release Notes

Create detailed release notes including:
- New features
- Bug fixes
- Breaking changes
- Upgrade instructions
- Known issues

## WordPress.org Submission

### Preparation

1. **Plugin Review Guidelines**
   - Read WordPress Plugin Review Guidelines
   - Ensure compliance with all requirements
   - Test thoroughly

2. **Required Files**
   - `readme.txt` (WordPress.org format)
   - Main plugin file with proper headers
   - All necessary PHP files
   - Assets for plugin directory

### Submission Process

1. **Create WordPress.org Account**
   - Register at wordpress.org
   - Verify email address

2. **Submit Plugin**
   - Go to wordpress.org/plugins/developers/add/
   - Upload ZIP file
   - Fill out submission form
   - Wait for review

3. **Review Process**
   - Initial automated checks
   - Manual review by WordPress team
   - Feedback and required changes
   - Approval and SVN access

### SVN Management

```bash
# Checkout SVN repository
svn co https://plugins.svn.wordpress.org/adminx-productivity/

# Add files to trunk
cp -r plugin-files/* trunk/

# Add new files
svn add trunk/*

# Commit changes
svn ci -m "Initial commit"

# Create tag for release
svn cp trunk tags/1.0.0
svn ci -m "Tag version 1.0.0"
```

## Deployment Checklist

### Pre-deployment

- [ ] Code review completed
- [ ] All tests passing
- [ ] Documentation updated
- [ ] Version numbers updated
- [ ] Changelog updated
- [ ] Security scan completed
- [ ] Performance testing completed
- [ ] Backup created

### Deployment

- [ ] Plugin packaged correctly
- [ ] Files uploaded to distribution
- [ ] Version tagged in Git
- [ ] Release notes published
- [ ] WordPress.org updated (if applicable)
- [ ] Documentation site updated

### Post-deployment

- [ ] Deployment verified
- [ ] Monitoring alerts configured
- [ ] Support channels notified
- [ ] User communication sent
- [ ] Analytics tracking verified
- [ ] Feedback collection enabled

### Rollback Plan

1. **Immediate Issues**
   - Revert to previous version
   - Notify users of rollback
   - Investigate and fix issues

2. **Rollback Process**
   ```bash
   # Revert Git tag
   git revert v1.0.0
   
   # Create hotfix release
   git tag -a v1.0.1 -m "Hotfix release"
   
   # Update distribution
   # Notify users
   ```

## Continuous Integration

### GitHub Actions

The plugin includes automated CI/CD workflows:

- **Code Quality**: PHP syntax, WordPress standards
- **Security**: Vulnerability scanning
- **Testing**: Unit and integration tests
- **Build**: Automated packaging
- **Deployment**: Automated releases

### Monitoring

- Error tracking and logging
- Performance monitoring
- User feedback collection
- Usage analytics
- Security monitoring

---

*This deployment guide ensures consistent, reliable releases of the AdminX Productivity plugin while maintaining high quality and security standards.*