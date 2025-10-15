# 🏨 Sistema de Gestión Hotelera

Sistema web completo para la administración y gestión de operaciones hoteleras, desarrollado en PHP con MySQL. Permite la gestión integral de reservas, habitaciones, huéspedes y reportes.

## 📋 Descripción del Proyecto

El Sistema de Gestión Hotelera es una aplicación web diseñada para facilitar la administración de hoteles. Proporciona una interfaz intuitiva para que los administradores puedan gestionar eficientemente todos los aspectos operativos del hotel, desde el registro de huéspedes hasta el control de habitaciones y la generación de reportes.

## ✨ Características Principales

- **🔐 Sistema de Autenticación**: Login seguro para administradores
- **📊 Dashboard Interactivo**: Visualización de estadísticas y métricas clave
- **🛏️ Gestión de Habitaciones**: Control de disponibilidad, tipos y estados de habitaciones
- **👥 Gestión de Huéspedes**: Registro y administración de información de clientes
- **📅 Gestión de Reservas**: Sistema completo de reservaciones con seguimiento
- **📈 Reportes**: Generación de reportes y análisis de datos
- **💻 Interfaz Responsiva**: Diseño adaptable a diferentes dispositivos

## 🛠️ Tecnologías Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP
- **Base de Datos**: MySQL
- **Servidor Web**: Apache (XAMPP)

### Instalación Paso a Paso

#### 1. Clonar el Repositorio

```bash
# Clonar el repositorio desde GitHub
git clone https://github.com/DiegoJsH/GestionHotelera.git

# Navegar al directorio del proyecto
cd GestionHotelera
```

#### 2. Configurar la Conexión a la Base de Datos

Edita el archivo `includes/db_connection.php` si es necesario:

```php
<?php
    $host = "localhost";
    $user = "root";           // Tu usuario de MySQL
    $password = "";           // Tu contraseña de MySQL
    $database = "sistemahoteleria";

    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
?>
```

## 👥 Contribuir al Proyecto

Si deseas contribuir al proyecto:
```bash
1. **Realiza tus cambios y haz commit**
   git add .
   git commit -m "Agregar nueva funcionalidad: descripción"

2. **Push a tu fork**
   git push origin feature/nueva-funcionalidad
```


## 🔄 Comandos Git Útiles

```bash
# Ver el estado de los archivos
git status

# Ver el historial de commits
git log --oneline

# Actualizar tu copia local con los últimos cambios
git pull origin main

# Crear una nueva rama
git checkout -b nombre-de-rama

# Cambiar entre ramas
git checkout nombre-de-rama

# Ver todas las ramas
git branch -a

# Fusionar una rama con la actual
git merge nombre-de-rama
```
