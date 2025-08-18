import re
from datetime import datetime

# Regex for Unix timestamps (10 or 13 digits)
_EPOCH_RE = re.compile(r"^\s*-?\d{10}(?:\d{3})?\s*$")
# Regex for EXIF-style date: YYYY:MM:DD ...
_EXIF_DATE_RE = re.compile(r"^(\d{4}):(\d{2}):(\d{2})([ T])")

def _normalize_timezone_tail(s: str) -> str:
	"""
	Normalize time zone suffixes to ISO-8601 style.
	Example: Z -> +00:00, +0200 -> +02:00, +02 -> +02:00
	"""
	s = s.strip()
	if s.endswith("Z"):
		return s[:-1] + "+00:00"
	if s.endswith(" UTC"):
		return s[:-4] + "+00:00"
	s = re.sub(r"([+-]\d{2})(\d{2})$", r"\1:\2", s)  # +HHMM -> +HH:MM
	s = re.sub(r"([+-]\d{2})$", r"\1:00", s)         # +HH   -> +HH:00
	return s

def _normalize_datetime_str(s: str) -> str:
	"""
	Clean up a date/time string into something close to ISO 8601.
	- Fix EXIF date (YYYY:MM:DD -> YYYY-MM-DD)
	- Replace comma milliseconds with dot
	- Add missing seconds if only HH:MM
	- Normalize time zones
	"""
	s = s.strip()
	s = re.sub(r"\s+", " ", s.replace("\u00A0", " "))

	# EXIF style date
	s = _EXIF_DATE_RE.sub(lambda m: f"{m.group(1)}-{m.group(2)}-{m.group(3)}{m.group(4)}", s)

	# Replace "T" with space
	s = s.replace("T", " ")

	# Milliseconds with comma -> dot
	s = re.sub(r"(\d{2}:\d{2}:\d{2}),(\d+)", r"\1.\2", s)

	# Add :00 seconds if only HH:MM
	s = re.sub(r"(\d{4}-\d{2}-\d{2} \d{2}:\d{2})(?!:)", r"\1:00", s)

	# Normalize time zone suffix
	s = _normalize_timezone_tail(s)
	return s

def parse_datetime_local(text: str) -> datetime:
	"""
	Parse various date/time string formats into a naive datetime in local time.
	Supported:
	- EXIF style: 'YYYY:MM:DD HH:MM:SS±HH:MM' or without TZ
	- ISO 8601:   'YYYY-MM-DD HH:MM:SS(.fff)(Z|±HH[:MM])'
	- Unix epoch: 10/13 digit integer (sec/ms)
	Returns a naive datetime (local time).
	"""
	if not text or not isinstance(text, str):
		return (None)

	s = text.strip()

	# 1) Epoch timestamps
	if _EPOCH_RE.match(s):
		val = int(s)
		if len(s) >= 13:
			return datetime.fromtimestamp(val / 1000)  # local time
		return datetime.fromtimestamp(val)            # local time

	# 2) Normalize to ISO-like string
	s_norm = _normalize_datetime_str(s)

	# 3) Try fromisoformat
	try:
		dt = datetime.fromisoformat(s_norm)
	except Exception:
		dt = None

	# 4) Try strptime fallbacks
	if dt is None:
		for pat in ("%Y-%m-%d %H:%M:%S%z",
					"%Y-%m-%d %H:%M:%S",
					"%Y-%m-%d %H:%M%z",
					"%Y-%m-%d %H:%M"):
			try:
				dt = datetime.strptime(s_norm, pat)
				break
			except Exception:
				pass

	# 5) Drop tzinfo -> convert to local time
	if dt.tzinfo is not None:
		dt = dt.astimezone().replace(tzinfo=None)

	return (dt)
