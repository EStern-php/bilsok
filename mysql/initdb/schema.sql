CREATE TABLE IF NOT EXISTS cars (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id VARCHAR(64) NOT NULL UNIQUE,
  make VARCHAR(50),
  model VARCHAR(80),
  model_year SMALLINT UNSIGNED,
  regno VARCHAR(16),
  price INT UNSIGNED,
  mileage INT UNSIGNED,
  location VARCHAR(120),
  INDEX ix_make (make),
  INDEX ix_year (model_year),
  INDEX ix_regno (regno),
  INDEX ix_make_year_price (make, model_year, price),
  INDEX ix_year_price (model_year, price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;