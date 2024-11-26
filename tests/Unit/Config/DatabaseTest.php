<?php
namespace Tests\Unit\Config;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Config\Database;
use PDO;
use Exceptions\DatabaseException;

class DatabaseTest extends TestCase
{
    private $database;
    private $envPath;

    protected function setUp(): void
    {
        $this->envPath = __DIR__ . '/../../../.env';
        
        // Crear un archivo .env temporal para las pruebas
        $envContent = "DB_HOST=test_host\n" .
                     "DB_USER=test_user\n" .
                     "DB_PASSWORD=test_pass\n" .
                     "DB_NAME=test_db";
        file_put_contents($this->envPath, $envContent);
        
        $this->database = new Database();
    }

    protected function tearDown(): void
    {
        // Limpiar variables de entorno
        putenv('DB_HOST');
        putenv('DB_USER');
        putenv('DB_PASSWORD');
        putenv('DB_NAME');
        
        // Eliminar archivo .env temporal
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }
    }

    /** @test */
    public function constructor_carga_variables_desde_env_file(): void
    {
        $originalContent = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

        // Asegurarnos que el archivo .env tiene todas las variables necesarias
        $envContent = "DB_HOST=test_host\n" .
                     "DB_USER=test_user\n" .
                     "DB_PASSWORD=test_pass\n" .
                     "DB_NAME=test_db";
        file_put_contents($this->envPath, $envContent);

        $database = new Database();
        
        $reflection = new \ReflectionClass($database);
        
        // Verificar todas las propiedades
        $properties = ['host', 'user', 'password', 'database'];
        $expectedValues = [
            'host' => 'test_host',
            'user' => 'test_user',
            'password' => 'test_pass',
            'database' => 'test_db'
        ];

        foreach ($properties as $prop) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $this->assertEquals($expectedValues[$prop], $property->getValue($database));
        }

        // Restaurar el archivo original
        if ($originalContent !== null) {
            file_put_contents($this->envPath, $originalContent);
        } else {
            unlink($this->envPath);
        }
    }

    /** @test */
    public function constructor_maneja_env_file_no_existente(): void
    {
        $originalContent = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
        
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        $database = new Database();
        
        $reflection = new \ReflectionClass($database);
        $hostProperty = $reflection->getProperty('host');
        $hostProperty->setAccessible(true);
        
        $this->assertEquals('db', $hostProperty->getValue($database));

        if ($originalContent !== null) {
            file_put_contents($this->envPath, $originalContent);
        }
    }

    /** @test */
    public function constructor_maneja_env_file_invalido(): void
    {
        $originalContent = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;

        // Crear archivo .env inválido
        file_put_contents($this->envPath, "invalid content");

        $database = new Database();
        
        $reflection = new \ReflectionClass($database);
        $hostProperty = $reflection->getProperty('host');
        $hostProperty->setAccessible(true);
        
        $this->assertEquals('db', $hostProperty->getValue($database));

        if ($originalContent !== null) {
            file_put_contents($this->envPath, $originalContent);
        } else {
            unlink($this->envPath);
        }
    }

    /** @test */
    public function constructor_usa_variables_de_entorno(): void
    {
        $originalContent = file_exists($this->envPath) ? file_get_contents($this->envPath) : null;
        
        if (file_exists($this->envPath)) {
            unlink($this->envPath);
        }

        putenv('DB_HOST=env_host');
        
        $database = new Database();
        
        $reflection = new \ReflectionClass($database);
        $hostProperty = $reflection->getProperty('host');
        $hostProperty->setAccessible(true);
        
        $this->assertEquals('env_host', $hostProperty->getValue($database));

        if ($originalContent !== null) {
            file_put_contents($this->envPath, $originalContent);
        }
    }

    /** @test */
    public function connect_establece_conexion_exitosa(): void
    {
        // Configurar credenciales válidas
        $reflection = new \ReflectionClass($this->database);
        
        $properties = [
            'host' => 'localhost',
            'user' => 'test_user',
            'password' => 'test_pass',
            'database' => 'test_db'
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->database, $value);
        }

        try {
            $connection = $this->database->connect();
            $this->assertInstanceOf(PDO::class, $connection);
        } catch (DatabaseException $e) {
            $this->markTestSkipped('No se pudo establecer conexión con la base de datos de prueba');
        }
    }

    /** @test */
    public function connect_maneja_error_de_conexion(): void
    {
        // Configurar una base de datos con credenciales inválidas
        $reflection = new \ReflectionClass($this->database);
        
        $properties = [
            'host' => 'invalid_host',
            'user' => 'invalid_user',
            'password' => 'invalid_pass',
            'database' => 'invalid_db'
        ];

        foreach ($properties as $prop => $value) {
            $property = $reflection->getProperty($prop);
            $property->setAccessible(true);
            $property->setValue($this->database, $value);
        }

        $errorLogFile = tempnam(sys_get_temp_dir(), 'php_error_log');
        ini_set('error_log', $errorLogFile);

        try {
            $this->expectException(DatabaseException::class);
            $this->database->connect();
        } finally {
            $errorLogContent = file_get_contents($errorLogFile);
            $this->assertStringContainsString('Error de conexión:', $errorLogContent);
            unlink($errorLogFile);
        }
    }
} 