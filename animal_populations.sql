
CREATE TABLE `animal_populations` (
  `id` int(11) NOT NULL,
  `range_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `animal_type` enum('Cow','Buffalo','Goat','Chicken','Pig','Others') NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `animal_populations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_range_year_animal_type` (`range_id`,`year`,`animal_type`);


ALTER TABLE `animal_populations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;


ALTER TABLE `animal_populations`
  ADD CONSTRAINT `animal_populations_ibfk_1` FOREIGN KEY (`range_id`) REFERENCES `veterinary_ranges` (`id`) ON DELETE CASCADE;
COMMIT;

