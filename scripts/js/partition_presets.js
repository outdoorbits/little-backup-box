function PresetPartitionChange(field) {
	var PresetTarget = document.getElementById("preset_target");
	var PresetSource = document.getElementById("preset_source");

	if (PresetTarget.value == PresetSource.value) {
		if (document.activeElement.id == "preset_target") {
			PresetSource.value	= '';
		} else {
			PresetTarget.value	= '';
		}
	}
}

