<?php exit() ?>--by ferrarino1com 80.31.234.122
_G.SafeLoader = function(b)
	local ml = function(t) load(t,math.random(100,999).."script",nil,_ENV)() end
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

	t = {-51, 161, 361, 339, 307, 321, 187, 181, 213, 59, 77, 73, 15, 111, 177, 151, 73, 89, 191, 163, 153, 189, 85, 47, 129, 133, 133, 161, 187, 165, 135, 105, 111, 141, 99, 85, 89, 105, 147, 91, 125, 141, -21, -83, 85, 51, -121, -169, -223, -31, 169, 147, 115, 129, -5, 9, 5, -111, -115, -81, 73, 133, 109, 101, 39, 25, 77, 63, 61, 77, -49, -59, -43, -197, -209, -185, -217, -245, -79, 79, 43, 37, -101, -251, -207, -61, 47, 21, 7, -127, -267, -331, -399, -207, -7, -29, -61, -47, -181, -177, -181, -287, -291, -257, -103, -43, -67, -75, -137, -151, -99, -113, -115, -99, -225, -235, -219, -373, -385, -361, -393, -421, -265, -143, -141, -109, -275, -427, -383, -237, -129, -155, -169, -303, -443, -507, -575, -457, -277, -191, -267, -273, -165, -179, -227, -277, -277, -259, -301, -299, -237, -209, -227, -233, -373, -541, -413, -273, -277, -281, -285, -289, -293, -297, -299, -305, -313, -435, -445, -313, -319, -349, -333, -339, -371, -483, -483, -357, -341, -499, -633, -641, -665, -505, -375, -409, -523, -501, -389, -393, -537, -677, -685, -709, -543, -493, -663, -697, -677, -641, -543, -449, -445, -571, -673, -647, -535, -479, -481, -483, -507, -631, -603, -605, -751, -745, -773, -793, -641, -599, -759, -793, -773, -737, -639, -545, -541, -667, -769, -743, -631, -575, -577, -579, -603, -727, -709, -711, -851, -873, -761, -595, -583, -623, -615, -607, -621, -615, -761, -903, -923, -807, -655, -661, -803, -987, }	
		if _G.SS then
		ml(D(t))
	end
end
