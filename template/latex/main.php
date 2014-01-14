<?php
/*
 * Copyright (c) 2012, Josef Kufner  <jk@frozen-doe.net>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

function TPL_latex__core__main($t, $id, $d, $so)
{
	header('Content-Type: text/plain; encoding=UTF-8');
	echo	"%\n",
		"%  ", isset($so['page_title_format'])
				? sprintf($so['page_title_format'], @$so['page_title'])
				: @$so['page_title'], "\n",
		"%\n",
		"\\documentclass[12pt,english]{book}\n",
		"\\usepackage{ae,aecompl}\n",
		"\\usepackage{avant}\n",
		"\\renewcommand{\\ttdefault}{lmtt}\n",
		"\\renewcommand{\\familydefault}{\\rmdefault}\n",
		"\\usepackage[T1]{fontenc}\n",
		"\\usepackage[utf8]{inputenc}\n",
		"\\usepackage[a4paper]{geometry}\n",
		"\\geometry{verbose,tmargin=28mm,bmargin=32mm,lmargin=35mm,rmargin=20mm,headheight=14.5pt}\n",
		"\\usepackage{babel}\n",
		"\\usepackage{graphicx}\n",
		"\\usepackage{color}\n",
		"\\usepackage{tabularx}\n",
		"\\usepackage{fancyhdr}\n",
		"\\pagestyle{fancy}\n",
		"\\renewcommand{\headrulewidth}{0.2pt}\n",
		"\\renewcommand{\\footnoterule}{\\noindent\\rule{15em}{0.2pt}\\vspace{2pt}}\n",
		"\\fancyhead[RE,LO]{}\n",
		"\\fancyhead[LE,RO]{{\sc\leftmark}}\n",
		"\\renewcommand{\chaptermark}[1]{\markboth{\\thechapter\ #1}{}}\n",
		"\\renewcommand{\sectionmark}[1]{\markright{\\thesection\ #1}}\n",
		"\n",
		"% shrink too big images automagicaly\n",
		"\\newsavebox\\IBox\n",
		"\\let\\Includegrfx\\includegraphics\n",
		"\\renewcommand\\includegraphics[2][]{%\n",
		"\\sbox\\IBox{\\Includegrfx[#1]{#2}}%\n",
		"\\ifdim\\wd\\IBox>\\textwidth\\resizebox{\\textwidth}{!}{\\usebox\\IBox}\\else\n",
		"\\usebox\\IBox\\fi}\n",
		"\n";

	if (DEVELOPMENT_ENVIRONMENT) {
		echo	"% todo and fixme marks\n",
			"\\definecolor{TODOcolor}{RGB}{160, 0, 0}\n",
			"\\definecolor{TODO_TOCcolor}{RGB}{160, 64, 64}\n",
			"\\newcommand{\\TODO}[1]{\n",
			"	\\addtocontents{toc}{%\n",
			"	\\noindent \\leftskip 9em \\rightskip 3em\n",
			"	{\\scriptsize\\color{TODO_TOCcolor} {\\bf TODO:} {\\it #1}}\n",
			"	\\par}\n",
			"	\\par\\medskip\\noindent\\hangindent5em\n",
			"	\\makebox[5em][l]{\\bf\\textcolor{TODOcolor}{TODO:}}{\\it #1}\\medskip}\n",
			"\\newcommand{\\FIXME}[1]{\n",
			"	\\addtocontents{toc}{%\n",
			"	\\noindent \\leftskip 9em \\rightskip 3em\n",
			"	{\\scriptsize\\color{TODO_TOCcolor} {\\bf FIXME:} {\\it #1}}\n",
			"	\\par}\n",
			"	\\par\\medskip\\noindent\\hangindent5em\n",
			"	\\makebox[5em][l]{\\bf\\textcolor{TODOcolor}{FIXME:}}{\\it #1}\\medskip}\\par\n",
			"\n";
	}

	$t->processSlot('latex_preamble');

	echo	"\n\\begin{document}\n";

	$t->processSlot('latex_body');
	$t->processSlot('default');	// fallback

	echo	"\n\\end{document}\n";
}

