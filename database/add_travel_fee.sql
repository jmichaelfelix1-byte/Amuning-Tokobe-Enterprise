-- Add travel_fee column to photo_bookings table
ALTER TABLE photo_bookings 
ADD COLUMN travel_fee VARCHAR(20) DEFAULT '0.00' AFTER estimated_price;
