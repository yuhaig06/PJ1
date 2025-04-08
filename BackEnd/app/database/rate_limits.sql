CREATE TABLE IF NOT EXISTS `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) NOT NULL,
  `timestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_timestamp` (`ip`, `timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 