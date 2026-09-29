-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 02-09-2026 a las 08:34:06
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bdsopm`
--

DELIMITER $$
--
-- Funciones
--
DROP FUNCTION IF EXISTS `ConteoAcciones`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ConteoAcciones` (`p_IDobraproyecto` INT) RETURNS INT(11) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_Naccion INT DEFAULT 0;
    SET v_Naccion = (SELECT COUNT(IDAcciones) FROM TblD_Acciones WHERE IDobraproyecto = p_IDobraproyecto);
    RETURN v_Naccion;
END$$

DROP FUNCTION IF EXISTS `FinanciamientoObraproyecto`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `FinanciamientoObraproyecto` (`p_IDobraproyecto` INT, `p_Anio` INT) RETURNS VARCHAR(500) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_CadenaFuente VARCHAR(500);

    IF p_Anio = 0 THEN
        SELECT GROUP_CONCAT(DISTINCT PRF_Nombre SEPARATOR ',')
          INTO v_CadenaFuente
          FROM VW_Financiamientoinv
         WHERE IDObraproyecto = p_IDobraproyecto;
    ELSE
        SELECT GROUP_CONCAT(DISTINCT PRF_Nombre SEPARATOR ',')
          INTO v_CadenaFuente
          FROM VW_Financiamientoinv
         WHERE IDObraproyecto = p_IDobraproyecto
           AND OP_Año = p_Anio;
    END IF;

    RETURN IFNULL(v_CadenaFuente, '');
END$$

DROP FUNCTION IF EXISTS `localidadObraproyecto`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `localidadObraproyecto` (`p_IDobraproyecto` INT) RETURNS VARCHAR(500) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_CadenaLocalidades VARCHAR(500);

    SELECT GROUP_CONCAT(DISTINCT f.LCL_Nombre SEPARATOR ',')
      INTO v_CadenaLocalidades
      FROM TblD_Localidadproyectoobra e
      INNER JOIN TBLC_Localidades f ON e.IDLocalidad = f.IDLocalidad
     WHERE e.IDObraproyecto = p_IDobraproyecto;

    RETURN IFNULL(v_CadenaLocalidades, '');
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
