CREATE TABLE `<<prefix>>_mathoid` (
  `math_inputhash` varbinary(16) NOT NULL,
  `math_input` blob NOT NULL,
  `math_tex` blob DEFAULT NULL,
  `math_mathml` blob DEFAULT NULL,
  `math_svg` blob DEFAULT NULL,
  `math_style` tinyint(4) DEFAULT NULL,
  `math_input_type` tinyint(4) DEFAULT NULL,
  `math_png` mediumblob DEFAULT NULL,
  PRIMARY KEY (`math_inputhash`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;
