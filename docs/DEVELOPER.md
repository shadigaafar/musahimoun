## Developer Notes for Musahimoun Plugin

### Contributing
- Fork the repository and create a new feature branch for your changes.
- Follow WordPress coding standards for PHP, JavaScript, and CSS.
- Submit pull requests with detailed descriptions and reference any related issues.

### Architecture & Directory Responsibilities

- **`admin/`**: Contains only JavaScript files for admin-side UI logic (e.g., media uploaders, table scripts). No PHP or UI templates here.
- **`front/`**: Despite the name, contains both frontend blocks (JS/TS) and PHP meta box logic for post editing. Meta boxes are registered here for use in the post editor.
- **`inc/`**: Core PHP classes, services, and shared business logic (migration, contributor/role services, REST endpoints, etc).
- **`assets/`**: Static files (images, icons, banners, etc).

---

## Key Concepts & Data Model

### What is a Contributor?
- A **contributor** is any person credited for a post (author, fact-checker, translator, etc). They may be a WordPress user or a guest (not a registered user).

### Contributor vs. Author
| Term         | Definition                                                                                 |
|--------------|--------------------------------------------------------------------------------------------|
| **Contributor** | Any person credited for a post (author, fact-checker, translator, etc). Not always a WP user. |
| **Author**       | A WordPress user with the "author" role, typically the main writer of a post.               |

**Difference:**
- An **author** is a specific type of contributor (the main writer, must be a WP user).
- A **contributor** can be any credited person, including guest authors, fact-checkers, etc.

### What is a Role?
- A **role** in Musahimoun is a display label for a contributor’s function (e.g., Author, Editor, Translator, Fact-checker). It is not related to permissions.

### Role vs. WordPress User Role vs. Role Assignment
| Term                | Definition                                                                                   |
|---------------------|----------------------------------------------------------------------------------------------|
| **Role (Musahimoun)**         | A label for a contributor’s function (e.g., Author, Editor, Translator, Fact-checker).      |
| **WordPress User Role**       | Built-in WP permission group (e.g., administrator, editor, author, subscriber).            |
| **Role Assignment**           | The act of linking a contributor to a specific role for a post (e.g., John as Fact-checker).|

**Difference:**
- **Musahimoun roles** are for display/credit, not permissions.
- **WP user roles** control backend permissions/capabilities.
- **Role assignment** is the mapping of a contributor to a role for a specific post.

### Guest Author vs. Contributor
| Term           | Definition                                                                                 |
|----------------|--------------------------------------------------------------------------------------------|
| **Guest Author**   | A contributor who is not a registered WP user (e.g., external expert, guest writer).       |
| **Contributor**    | Any credited person (can be a WP user or a guest).                                        |

**Difference:**
- All guest authors are contributors, but not all contributors are guests (some are WP users).

### What is a Role Assignment?
- A **role assignment** links a contributor (person) to a role (function) for a specific post.
- Example:
    - Post 123:
        - Alice (WP user) as Author
        - Bob (guest) as Fact-checker

---

## Database Structure

### Main Tables
| Table Name                | Purpose                                                      |
|---------------------------|-------------------------------------------------------------|
| `mshmn_contributors`      | Stores all contributors (users and guests).                 |
| `mshmn_roles`             | Stores custom roles (Author, Editor, etc).                  |


### Role Assignments meta field
No table for role assigments. It lives in post meta field called `mshmn_role_assignments` and it is structures like this: 
```
    array(
        array(
            'role' => 1,
            'contributors => array(1, 4, 8)
        )
    )
```

---

#### Architectural Flow
1. **Initialization:** The main PHP file loads core classes from `inc/` and registers hooks.
2. **Admin Area:** JS scriipts are loaded from `admin/`.
3. **Frontend:** Blocks and UI components in `front/` interact with REST API endpoints for dynamic data.
4. **REST API:** Custom endpoints handle contributor and role management, accessible from both admin and frontend.

## Summary Table

| Concept         | Musahimoun Meaning                | WordPress Core Meaning         | Example in Plugin                |
|-----------------|-----------------------------------|-------------------------------|----------------------------------|
| Contributor     | Any credited person               | User with 'contributor' role  | Guest author, fact-checker, etc. |
| Author          | Main writer (a type of contributor)| User with 'author' role       | Alice as Author                  |
| Role            | Display label for contributor     | Permission group              | Fact-checker, Translator         |
| Role Assignment | Contributor+Role+Post mapping     | N/A                           | Bob as Fact-checker for Post 123 |
| Guest Author    | Contributor not in WP users table | N/A                           | Bob Guest                        |

---
### Build Tools
- JavaScript/TypeScript source code is in `front/src/`.
- Webpack (`webpack.config.js`) bundles and optimizes frontend assets.
- To build assets:
    1. Navigate to the `front/` directory.
    2. Run `npm install` to install dependencies.
    3. Run `npm run build` to generate assets for both development and production modes.

### Packaging (`package-plugin.ps1`)
- The `package-plugin.ps1` PowerShell script creates a distributable ZIP archive of the plugin.
- The script excludes development files and directories such as `node_modules`, test suites, and Docker configuration files.
- To package the plugin, run the script from the project root: `./package-plugin.ps1`.

### Docker
- Multiple Dockerfiles are provided for different PHP versions (e.g., `Dockerfile.74`, `Dockerfile.81`) for compatibility testing.
- The `docker-compose.yml` file sets up a local development environment with all necessary services.
- To start the environment, use: `docker-compose up`.

### Other Notes
- All static assets (images, icons, banners) are stored in the `assets/` directory.
- For plugin usage, installation, and additional documentation, refer to `README.md`.
